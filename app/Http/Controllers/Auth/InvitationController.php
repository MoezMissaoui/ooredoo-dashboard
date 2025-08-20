<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOperator;
use App\Models\UserOtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\InvitationMail;

class InvitationController extends Controller
{
    /**
     * Afficher la liste des invitations
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            $invitations = Invitation::with(['invitedBy', 'role'])->orderBy('created_at', 'desc')->paginate(20);
        } else {
            // Un admin ne voit que les invitations pour ses opérateurs
            $userOperators = $user->operators->pluck('operator_name')->toArray();
            $invitations = Invitation::where(function($query) use ($user, $userOperators) {
                // Ses propres invitations
                $query->where('invited_by', $user->id)
                // OU invitations pour ses opérateurs (même si créées par un super admin)
                ->orWhereIn('operator_name', $userOperators);
            })
            ->with(['invitedBy', 'role'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        }
        
        return view('admin.invitations.index', compact('invitations'));
    }

    /**
     * Afficher le formulaire de création d'invitation
     */
    public function create()
    {
        $user = auth()->user();
        
        // Les rôles disponibles selon le niveau de l'utilisateur connecté
        if ($user->isSuperAdmin()) {
            $roles = Role::active()->get();
            $operators = $this->getAllOperators();
        } else {
            // Un admin ne peut inviter que des collaborateurs
            $roles = Role::where('name', 'collaborator')->active()->get();
            $operators = $user->operators->pluck('operator_name', 'operator_name');
        }
        
        return view('admin.invitations.create', compact('roles', 'operators'));
    }

    /**
     * Créer une nouvelle invitation
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'email' => 'required|email|unique:users,email|unique:invitations,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'operator_name' => 'required|string',
            'message' => 'nullable|string|max:500'
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée ou a déjà été invitée.',
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'role_id.required' => 'Le rôle est obligatoire.',
            'operator_name.required' => 'L\'opérateur est obligatoire.'
        ]);

        // Vérifier les permissions
        $role = Role::find($request->role_id);
        if (!$user->isSuperAdmin() && $role->name !== 'collaborator') {
            return back()->with('error', 'Vous ne pouvez inviter que des collaborateurs.');
        }

        // Vérifier que l'opérateur est dans la liste autorisée
        if (!$user->isSuperAdmin()) {
            $userOperators = $user->operators->pluck('operator_name');
            if (!$userOperators->contains($request->operator_name)) {
                return back()->with('error', 'Vous ne pouvez pas inviter pour cet opérateur.');
            }
        }

        try {
            // Créer l'invitation
            $invitation = Invitation::create([
                'email' => $request->email,
                'token' => Str::random(64),
                'invited_by' => $user->id,
                'role_id' => $request->role_id,
                'operator_name' => $request->operator_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'expires_at' => now()->addDays(7), // Expiration dans 7 jours
                'additional_data' => [
                    'message' => $request->message,
                    'invited_by_name' => $user->name
                ]
            ]);

            // Envoyer l'email d'invitation
            $invitationUrl = route('auth.invitation', $invitation->token);
            
            try {
                Mail::to($invitation->email)->send(new InvitationMail($invitation, $invitationUrl));
                
                Log::info("=== INVITATION ENVOYÉE ===");
                Log::info("Email: {$invitation->email}");
                Log::info("Invité par: {$user->name}");
                Log::info("Lien d'invitation: {$invitationUrl}");
                
                return redirect()->route('admin.invitations.index')
                               ->with('success', "Invitation envoyée avec succès à {$invitation->email}.");
                               
            } catch (\Exception $e) {
                Log::error("Erreur envoi email invitation: " . $e->getMessage());
                
                return redirect()->route('admin.invitations.index')
                               ->with('success', "Invitation créée. Erreur d'envoi email - Lien pour test: {$invitationUrl}");
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'envoi de l\'invitation: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer une invitation
     */
    public function destroy(Invitation $invitation)
    {
        $user = auth()->user();
        
        // Vérifier les permissions
        if (!$user->isSuperAdmin() && $invitation->invited_by !== $user->id) {
            abort(403, 'Vous ne pouvez supprimer que vos propres invitations.');
        }

        if ($invitation->status === 'accepted') {
            return back()->with('error', 'Vous ne pouvez pas supprimer une invitation déjà acceptée.');
        }

        try {
            $invitation->delete();
            return redirect()->route('admin.invitations.index')
                           ->with('success', 'Invitation supprimée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Traiter l'acceptation d'une invitation via OTP
     */
    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'invitation_token' => 'required|string'
        ]);

        // Vérifier le code OTP
        $otpCode = UserOtpCode::where('email', $request->email)
                             ->where('code', $request->code)
                             ->where('type', 'invitation')
                             ->where('invitation_token', $request->invitation_token)
                             ->valid()
                             ->first();

        if (!$otpCode) {
            return back()->with('error', 'Code de vérification invalide ou expiré.');
        }

        // Vérifier l'invitation
        $invitation = Invitation::where('token', $request->invitation_token)
                                ->where('email', $request->email)
                                ->pending()
                                ->first();

        if (!$invitation) {
            return back()->with('error', 'Invitation invalide ou expirée.');
        }

        DB::beginTransaction();
        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $invitation->first_name . ' ' . $invitation->last_name,
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'email' => $invitation->email,
                'password' => Hash::make(Str::random(16)), // Mot de passe temporaire
                'role_id' => $invitation->role_id,
                'status' => 'active',
                'is_otp_enabled' => true, // L'utilisateur invité utilise OTP par défaut
                'created_by' => $invitation->invited_by
            ]);

            // Assigner l'opérateur
            UserOperator::create([
                'user_id' => $user->id,
                'operator_name' => $invitation->operator_name,
                'is_primary' => true,
                'assigned_by' => $invitation->invited_by
            ]);

            // Marquer l'invitation comme acceptée
            $invitation->accept();

            // Marquer le code OTP comme utilisé
            $otpCode->markAsUsed();

            // Connecter l'utilisateur
            auth()->login($user);
            $request->session()->regenerate();

            // Mettre à jour les informations de dernière connexion
            $user->updateLastLogin($request->ip());

            DB::commit();

            Log::info("Invitation acceptée avec succès pour: " . $user->email);

            return redirect()->route('dashboard')
                           ->with('success', 'Bienvenue ! Votre compte a été créé avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Erreur lors de l'acceptation de l'invitation: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création du compte: ' . $e->getMessage());
        }
    }

    /**
     * Annuler une invitation (marquer comme cancelled)
     */
    public function cancel(Invitation $invitation)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && $invitation->invited_by !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que vos propres invitations.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Cette invitation ne peut plus être annulée.');
        }

        $invitation->cancel();
        return back()->with('success', 'Invitation annulée avec succès.');
    }

    /**
     * Renvoyer une invitation (créer un nouveau token)
     */
    public function resend(Invitation $invitation)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && $invitation->invited_by !== $user->id) {
            abort(403, 'Vous ne pouvez renvoyer que vos propres invitations.');
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Cette invitation ne peut pas être renvoyée.');
        }

        // Générer un nouveau token et prolonger l'expiration
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7)
        ]);

        $invitationUrl = route('auth.invitation', $invitation->token);
        
        try {
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $invitationUrl));
            
            Log::info("=== INVITATION RENVOYÉE ===");
            Log::info("Email: {$invitation->email}");
            Log::info("Nouveau lien: {$invitationUrl}");
            
            return back()->with('success', "Invitation renvoyée avec succès à {$invitation->email}.");
            
        } catch (\Exception $e) {
            Log::error("Erreur renvoi email invitation: " . $e->getMessage());
            
            return back()->with('success', "Invitation mise à jour. Erreur d'envoi email - Nouveau lien: {$invitationUrl}");
        }
    }

    /**
     * Récupérer tous les opérateurs disponibles
     */
    private function getAllOperators(): array
    {
        return DB::table('country_payments_methods')
                 ->distinct()
                 ->pluck('country_payments_methods_name', 'country_payments_methods_name')
                 ->toArray();
    }
}