<?php
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Utilities\CONST_DEF;

new class extends Component
{
    use WithPagination;

    // Search and Sort
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    // Form Properties
    public $userId; // For editing
    public $name = '';
    public $email = '';
    public $password = '';

    public $entity_id = '';

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function mount(){
        $entity_id = request()->session()->get(CONST_DEF::$SESSION_ENTITY_ID);
    }

    public function updatedSearch() {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function users() {
        return User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    public function create() {
        $this->reset(['userId', 'name', 'email', 'password']);
        $this->modal('user-modal')->show();
    }

    public function edit(User $user) {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->modal('user-modal')->show();
    }

    public function save() {
        $this->validate([
            'name' => 'required|string|max:255',
            // Fix: Use ignore() properly to avoid the empty string issue
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'password' => $this->userId ? 'nullable|min:6' : 'required|min:6',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($this->password) $data['password'] = Hash::make($this->password);
            else unset($data['password']);
            $user->update($data);
        } else {
            //$data['id'] = HelperUtil::generateRandomAccNo()
            $data['password'] = Hash::make($this->password);
            $data['entity_id'] = $this->entity_id;
            User::create($data);
        }

        $this->modal('user-modal')->close();
        Flux::toast('User saved successfully.');
    }

    public function delete(User $user) {
        $user->delete();
        Flux::toast('User deleted.');
    }
}; ?>

<div class="space-y-6">
    <!-- Header & Search -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Users</flux:heading>
            <flux:text class="mt-1">Manage your team members and their account permissions.</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search users..." class="w-64" />
            <flux:button wire:click="create" icon="plus" variant="primary">Add User</flux:button>
        </div>
    </div>

    <!-- Table -->
    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Joined</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="xs" :initials="$user->initials()" />
                        {{ $user->name }}
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500">{{ $user->email }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->created_at->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item wire:click="edit({{ $user->id }})" icon="pencil-square">Edit</flux:menu.item>
                                <flux:menu.item wire:click="delete({{ $user->id }})" icon="trash" variant="danger">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Upsert Modal -->
    <flux:modal name="user-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $userId ? 'Edit User' : 'Create User' }}</flux:heading>
                <flux:text class="mt-2">Provide the details for this account.</flux:text>
            </div>

            <flux:input wire:model="name" label="Name" />
            <flux:input wire:model="email" label="Email" type="email" />
            <flux:input wire:model="password" label="Password" type="password" hint="{{ $userId ? 'Leave blank to keep current password' : '' }}" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
