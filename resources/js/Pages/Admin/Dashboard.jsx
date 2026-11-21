import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function AdminDashboard() {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <AuthenticatedLayout>
            <Head title="Admin Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h1 className="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
                        <p className="mt-2 text-gray-600">
                            Welcome, {user.full_name} ({user.user_id})
                        </p>
                        <p className="mt-4 text-gray-500">
                            Roles: {user.roles.join(', ')}
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}