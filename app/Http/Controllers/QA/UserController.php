<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const ROLES = ['qa_director', 'administrator', 'campus_incharge'];

    public function index()
    {
        $users = User::with(['roles', 'college'])->orderBy('name')->get();

        return view('qa.users.index', compact('users'));
    }

    public function create()
    {
        return $this->form(new User());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => empty($data['password']) ? null : Hash::make($data['password']),
            'college_id' => $data['role'] === 'campus_incharge' ? $data['college_id'] : null,
        ]);
        $user->syncRoles($data['role']);

        return redirect()->route('qa.users.index')->with('success', "Account created for {$user->name}.");
    }

    public function edit(User $user)
    {
        return $this->form($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && $data['role'] !== $user->getRoleNames()->first()) {
            return back()->with('error', "You can't change your own role.");
        }

        if ($user->email !== $data['email']) {
            $user->google_sub = null; // a new email must be claimed by its own Google account
        }
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'college_id' => $data['role'] === 'campus_incharge' ? $data['college_id'] : null,
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles($data['role']);

        return redirect()->route('qa.users.index')->with('success', 'Account updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't delete your own account.");
        }

        $user->delete();

        return redirect()->route('qa.users.index')->with('success', 'Account removed.');
    }

    private function form(User $user)
    {
        return view('qa.users.form', [
            'user' => $user,
            'roles' => array_intersect_key(User::ROLE_LABELS, array_flip(self::ROLES)),
            'colleges' => College::orderBy('is_extension')->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $domain = config('oas.allowed_domain');
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => array_filter(['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user), $domain ? 'ends_with:@' . $domain : null]),
            'role' => ['required', Rule::in(self::ROLES)],
            'college_id' => 'nullable|required_if:role,campus_incharge|exists:colleges,id',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'college_id.required_if' => 'Pick the college or campus this in-charge handles.',
            'email.ends_with' => "Use their institutional email (@{$domain}). They sign in with that Google account.",
        ]);
    }
}
