import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

export default function ForceChangePassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.change'), {
            onFinish: () => setData('password', 'password_confirmation'),
        });
    };

    return (
        <AuthSplitLayout
            status={status}
            title="Change your password"
            subtitle="You must change your password before continuing."
        >
            <Head title="Change Password" />

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="current_password" value="Current Password" />

                    <TextInput
                        id="current_password"
                        type="password"
                        name="current_password"
                        value={data.current_password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        isFocused={true}
                        onChange={(e) => setData('current_password', e.target.value)}
                    />

                    <InputError message={errors.current_password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="New Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirm Password" />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />

                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-6 flex flex-col gap-3 sm:flex-row-reverse sm:items-center">
                    <PrimaryButton disabled={processing} className="w-full sm:w-auto sm:justify-center">
                        Change Password
                    </PrimaryButton>

                    {/* Sign out — EnsurePasswordIsChanged lets route('logout')
                        through while must_change_password is true. */}
                    <button
                        type="button"
                        onClick={() => router.post(route('logout'))}
                        className="w-full text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:w-auto sm:text-left"
                    >
                        Sign out
                    </button>
                </div>
            </form>
        </AuthSplitLayout>
    );
}
