import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import { usePage } from '@inertiajs/react';

export default function Edit() {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 max-w-xl">
                        <header>
                            <h2 className="text-lg font-medium text-gray-900">
                                Account Information
                            </h2>
                            <p className="mt-1 text-sm text-gray-600">
                                Your account details (read-only).
                            </p>
                        </header>

                        <dl className="mt-6 space-y-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">User ID</dt>
                                <dd className="mt-1 text-sm text-gray-900 font-mono">{user.user_id}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd className="mt-1 text-sm text-gray-900">{user.full_name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Roles</dt>
                                <dd className="mt-1 flex flex-wrap gap-2">
                                    {user.roles.map((role) => (
                                        <span
                                            key={role}
                                            className="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                                        >
                                            {role}
                                        </span>
                                    ))}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 max-w-xl">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}