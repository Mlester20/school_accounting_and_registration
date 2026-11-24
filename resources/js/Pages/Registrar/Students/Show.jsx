import { Head, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import CredentialsModal from '@/Components/CredentialsModal';
import ConfirmActionModal from '@/Components/ConfirmActionModal';
import { useState } from 'react';

export default function ShowStudent({ student, can }) {
    // Credentials come from Inertia's flash bag (page.flash), never history state.
    const pageFlash = usePage().flash || {};
    const credentials = pageFlash.credentials || [];
    const showCredentialsModal = credentials.length > 0;
    const [confirmAction, setConfirmAction] = useState(null);

    const handleCreateAccount = () => {
        router.post(route('registrar.students.account.store', student.id));
    };

    const handleResetPassword = () => {
        setConfirmAction({ type: 'reset', student });
    };

    const handleConfirmReset = () => {
        if (!confirmAction) return;
        router.post(route('registrar.students.account.reset-password', confirmAction.student.id), {
            onSuccess: () => setConfirmAction(null),
        });
    };

    const handleSetStatus = (status) => {
        setConfirmAction({ type: 'status', student, status });
    };

    const handleConfirmStatus = () => {
        if (!confirmAction) return;
        router.put(route('registrar.students.account.status', confirmAction.student.id), {
            status: confirmAction.status,
            onSuccess: () => setConfirmAction(null),
        });
    };

    const accountStatusBadge = () => {
        if (!student.has_account) {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No Account</span>;
        }
        if (student.account?.status === 'inactive') {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>;
        }
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>;
    };

    return (
        <AuthenticatedLayout>
            <Head title={student.full_name} />
            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">{student.full_name}</h1>
                            <p className="mt-1 text-gray-600">{student.student_id}</p>
                        </div>
                        <div className="flex space-x-2">
                            <Link href={route('registrar.students.edit', student.id)}>
                                <SecondaryButton>Edit</SecondaryButton>
                            </Link>
                            <Link href={route('registrar.students.index')}>
                                <SecondaryButton>Back to List</SecondaryButton>
                            </Link>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <div className="p-6 border-b border-gray-200">
                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Student ID</dt>
                                    <dd className="mt-1 text-sm text-gray-900 font-mono">{student.student_id}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{student.full_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Sex</dt>
                                    <dd className="mt-1 text-sm text-gray-900 capitalize">{student.sex}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Contact Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{student.contact_number || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Year Level</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{student.year_level}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Program</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {student.program?.code} - {student.program?.name}
                                        {student.program?.department && (
                                            <div className="text-xs text-gray-400">{student.program.department.code} - {student.program.department.name}</div>
                                        )}
                                    </dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500">Account Status</dt>
                                    <dd className="mt-1 flex items-center space-x-3">
                                        {accountStatusBadge()}
                                        {student.has_account && (
                                            <span className="text-sm text-gray-500">
                                                User ID: <span className="font-mono">{student.account?.user_id}</span>
                                            </span>
                                        )}
                                    </dd>
                                </div>
                                {student.has_account && student.account && (
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-gray-500">Must Change Password</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {student.account.must_change_password ? 'Yes' : 'No'}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        {student.has_account && (can.resetPassword || can.setStatus) && (
                            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-sm font-medium text-gray-700">Actions:</span>
                                    {can.resetPassword && (
                                        <SecondaryButton size="sm" onClick={handleResetPassword}>
                                            Reset Password
                                        </SecondaryButton>
                                    )}
                                    {can.setStatus && (
                                        <button
                                            type="button"
                                            onClick={() => handleSetStatus(student.account?.status === 'active' ? 'inactive' : 'active')}
                                            className={`px-3 py-1.5 text-sm rounded border ${
                                                student.account?.status === 'active'
                                                    ? 'border-red-300 text-red-700 hover:bg-red-50'
                                                    : 'border-green-300 text-green-700 hover:bg-green-50'
                                            }`}
                                        >
                                            {student.account?.status === 'active' ? 'Deactivate' : 'Activate'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {!student.has_account && can.createAccount && (
                            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm font-medium text-gray-700">This student doesn't have an account yet.</span>
                                    <PrimaryButton size="sm" onClick={handleCreateAccount}>
                                        Create Account
                                    </PrimaryButton>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Credentials Modal */}
                    <CredentialsModal
                        show={showCredentialsModal}
                        credentials={credentials}
                        onClose={() => {
                            // Clear the client-side flash so credentials leave React state.
                            router.flash({ credentials: null });
                        }}
                    />

                    {/* Confirm Action Modal */}
                    <ConfirmActionModal
                        show={!!confirmAction}
                        action={confirmAction?.type}
                        student={confirmAction?.student}
                        status={confirmAction?.status}
                        onClose={() => setConfirmAction(null)}
                        onConfirm={confirmAction?.type === 'reset' ? handleConfirmReset : handleConfirmStatus}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}