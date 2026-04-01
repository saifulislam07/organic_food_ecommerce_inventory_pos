<section>
    <p class="text-muted small mb-4">
        {{ (app()->getLocale() == 'bn' ? 'আপনার অ্যাকাউন্ট মুছে ফেলা হলে, এর সমস্ত তথ্য স্থায়ীভাবে মুছে যাবে। অ্যাকাউন্টটি মুছে ফেলার আগে, দয়া করে আপনার প্রয়োজনীয় কোনো তথ্য থাকলে ডাউনলোড করে নিন।' : 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
    </p>

    <button 
        class="btn btn-danger fw-bold"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        <i class="bi bi-trash"></i> {{ app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট মুছে ফেলুন' : 'Delete Account' }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-4 bg-white rounded shadow-lg">
            @csrf
            @method('delete')

            <h4 class="fw-bold mb-3 text-dark">
                {{ app()->getLocale() == 'bn' ? 'আপনি কি নিশ্চিত যে আপনি আপনার অ্যাকাউন্টটি মুছে ফেলতে চান?' : 'Are you sure you want to delete your account?' }}
            </h4>

            <p class="text-muted small mb-4">
                {{ app()->getLocale() == 'bn' ? 'একবার আপনার অ্যাকাউন্ট মুছে ফেলা হলে, এর সমস্ত ডেটা স্থায়ীভাবে মুছে যাবে। আপনার অ্যাকাউন্ট স্থায়ীভাবে মুছে ফেলতে চাইলে অনুগ্রহ করে আপনার পাসওয়ার্ড লিখুন।' : 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.' }}
            </p>

            <div class="mb-4">
                <label for="password" class="form-label fw-bold small text-uppercase">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড' : 'Password' }}</label>
                <input 
                    id="password"
                    name="password"
                    type="password"
                    class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                    placeholder="{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড লিখুন' : 'Password' }}"
                />
                @error('password', 'userDeletion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light fw-bold" x-on:click="$dispatch('close')">
                    {{ app()->getLocale() == 'bn' ? 'বাতিল' : 'Cancel' }}
                </button>

                <button type="submit" class="btn btn-danger fw-bold">
                    {{ app()->getLocale() == 'bn' ? 'মুছে ফেলুন' : 'Delete Account' }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
