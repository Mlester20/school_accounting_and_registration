import { useState, useMemo } from 'react';
import { Head, usePage, Link, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import CredentialsModal from '@/Components/CredentialsModal';
import ConfirmActionModal from '@/Components/ConfirmActionModal';

export default function StudentsIndex() {
    const { students, students_pagination, programs, filters, can } = usePage().props;
    // Credentials come from Inertia's flash bag (page.flash), NOT regular props:
    // it is excluded from browser history state, so Back/refresh never re-shows it.
    const pageFlash = usePage().flash || {};
    const [search, setSearch] = useState(filters.search || '');
    const [programId, setProgramId] = useState(filters.program_id || '');
    const [accountStatus, setAccountStatus] = useState(filters.account_status || '');
    const [selectedIds, setSelectedIds] = useState([]);
    const [selectAll, setSelectAll] = useState(false);

    const credentials = pageFlash.credentials || [];
    const bulkCredentials = pageFlash.bulk_credentials || [];
    const bulkFailed = pageFlash.bulk_failed || [];

    const showCredentialsModal = credentials.length > 0 || bulkCredentials.length > 0;
    const allCredentials = [...credentials, ...bulkCredentials];

    const [confirmAction, setConfirmAction] = useState(null); // { type: 'reset'|'status', student, status?: 'active'|'inactive' }

    // Handle checkbox selection
    const handleSelectAll = (checked) => {
        setSelectAll(checked);
        if (checked) {
            const ids = students
                .filter(s => !s.has_account)
                .map(s => s.id);
            setSelectedIds(ids);
        } else {
            setSelectedIds([]);
        }
    };

    const handleSelect = (id, checked) => {
        if (checked) {
            setSelectedIds([...selectedIds, id]);
        } else {
            setSelectedIds(selectedIds.filter(i => i !== id));
        }
    };

    // Pagination
    const handlePage = (page) => {
        router.get(route('registrar.students.index'), {
            page,
            search,
            program_id: programId,
            account_status: accountStatus,
        }, { preserveScroll: true });
    };

    // Search with debounce
    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        setTimeout(() => {
            router.get(route('registrar.students.index'), {
                search: value,
                program_id: programId,
                account_status: accountStatus,
            }, { preserveScroll: true });
        }, 300);
    };

    const handleFilterChange = () => {
        router.get(route('registrar.students.index'), {
            search,
            program_id: programId,
            account_status: accountStatus,
        }, { preserveScroll: true });
    };

    const handleCreateAccount = (student) => {
        router.post(route('registrar.students.account.store', student.id), {
            onSuccess: () => {
                // Credentials will be in flash
            },
        });
    };

    const handleBulkCreateAccounts = () => {
        if (selectedIds.length === 0) return;
        router.post(route('registrar.students.accounts.bulk'), {
            student_ids: selectedIds,
            onSuccess: () => {
                setSelectedIds([]);
                setSelectAll(false);
            },
        });
    };

    const handleResetPassword = (student) => {
        setConfirmAction({ type: 'reset', student });
    };

    const handleConfirmReset = () => {
        if (!confirmAction) return;
        router.post(route('registrar.students.account.reset-password', confirmAction.student.id), {
            onSuccess: () => {
                setConfirmAction(null);
            },
        });
    };

    const handleSetStatus = (student, status) => {
        setConfirmAction({ type: 'status', student, status });
    };

    const handleConfirmStatus = () => {
        if (!confirmAction) return;
        router.put(route('registrar.students.account.status', confirmAction.student.id), {
            status: confirmAction.status,
            onSuccess: () => {
                setConfirmAction(null);
            },
        });
    };

    const accountStatusBadge = (student) => {
        if (!student.has_account) {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No Account</span>;
        }
        if (student.account.status === 'inactive') {
            return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>;
        }
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Student Master List" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900">Student Master List</h1>
                            <p className="mt-1 text-gray-600">Manage students and create accounts</p>
                        </div>
                        {can.create && (
                            <Link href={route('registrar.students.create')}>
                                <PrimaryButton>Create Student</PrimaryButton>
                            </Link>
                        )}
                    </div>

                    {/* Filters */}
                    <div className="bg-white p-4 rounded-lg shadow mb-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <TextInput
                                    id="search"
                                    value={search}
                                    onChange={handleSearch}
                                    placeholder="Student ID, name..."
                                    className="w-full"
                                />
                            </div>
                            <div>
                                <label htmlFor="program_id" className="block text-sm font-medium text-gray-700 mb-1">Program</label>
                                <select
                                    id="program_id"
                                    value={programId}
                                    onChange={(e) => { setProgramId(e.target.value); handleFilterChange(); }}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                                >
                                    <option value="">All Programs</option>
                                    {programs.map(p => (
                                        <option key={p.id} value={p.id}>{p.code} - {p.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label htmlFor="account_status" className="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                <select
                                    id="account_status"
                                    value={accountStatus}
                                    onChange={(e) => { setAccountStatus(e.target.value); handleFilterChange(); }}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                                >
                                    <option value="">All</option>
                                    <option value="has_account">Has Account</option>
                                    <option value="no_account">No Account</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div className="flex items-end">
                                <button
                                    onClick={() => {
                                        setSearch(''); setProgramId(''); setAccountStatus('');
                                        router.get(route('registrar.students.index'), { preserveScroll: true });
                                    }}
                                    className="w-full text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Student Table */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left">
                                            {students.some(s => !s.has_account) && can.createAccount && (
                                                <Checkbox
                                                    checked={selectAll}
                                                    onChange={handleSelectAll}
                                                    disabled={students.filter(s => !s.has_account).length === 0}
                                                />
                                            )}
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Level</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {students.map((student) => (
                                        <tr key={student.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-4">
                                                {!student.has_account && can.createAccount && (
                                                    <Checkbox
                                                        checked={selectedIds.includes(student.id)}
                                                        onChange={(e) => handleSelect(student.id, e.target.checked)}
                                                    />
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-sm font-medium text-gray-900">{student.student_id}</td>
                                            <td className="px-4 py-4 text-sm text-gray-900">
                                                <Link href={route('registrar.students.show', student.id)} className="hover:text-indigo-600">
                                                    {student.full_name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-gray-600">
                                                {student.program?.code} - {student.program?.name}
                                                {student.program?.department && (
                                                    <div className="text-xs text-gray-400">{student.program.department.code}</div>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-gray-600">{student.year_level}</td>
                                            <td className="px-4 py-4">{accountStatusBadge(student)}</td>
                                            <td className="px-4 py-4 text-sm">
                                                <div className="flex items-center space-x-2">
                                                    {!student.has_account && can.createAccount && (
                                                        <SecondaryButton
                                                            size="sm"
                                                            onClick={() => handleCreateAccount(student)}
                                                            disabled={student.has_account}
                                                        >
                                                            Create Account
                                                        </SecondaryButton>
                                                    )}
                                                    {student.has_account && can.resetPassword && (
                                                        <SecondaryButton
                                                            size="sm"
                                                            onClick={() => handleResetPassword(student)}
                                                        >
                                                            Reset Password
                                                        </SecondaryButton>
                                                    )}
                                                    {student.has_account && can.setStatus && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleSetStatus(student, student.account.status === 'active' ? 'inactive' : 'active')}
                                                            className={`text-sm px-2 py-1 rounded border ${
                                                                student.account.status === 'active'
                                                                    ? 'border-red-300 text-red-700 hover:bg-red-50'
                                                                    : 'border-green-300 text-green-700 hover:bg-green-50'
                                                            }`}
                                                        >
                                                            {student.account.status === 'active' ? 'Deactivate' : 'Activate'}
                                                        </button>
                                                    )}
                                                    {student.has_account && (
                                                        <Link
                                                            href={route('registrar.students.show', student.id)}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            View
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Bulk Actions Bar */}
                        {selectedIds.length > 0 && can.createAccount && (
                            <div className="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                                <span className="text-sm text-gray-700">
                                    {selectedIds.length} student(s) selected
                                </span>
                                <PrimaryButton size="sm" onClick={handleBulkCreateAccounts}>
                                    Create Accounts ({selectedIds.length})
                                </PrimaryButton>
                            </div>
                        )}

                        {/* Pagination */}
                        {students_pagination && students_pagination.last_page > 1 && (
                            <div className="px-4 py-3 border-t border-gray-200">
                                <nav className="flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing {students_pagination.from} to {students_pagination.to} of {students_pagination.total} results
                                    </div>
                                    <div className="flex space-x-2">
                                        {students_pagination.links.map((link) => (
                                            <button
                                                key={link.url}
                                                onClick={() => link.active ? null : handlePage(link.label)}
                                                disabled={link.active || !link.url}
                                                className={`px-3 py-1 text-sm rounded-md ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : 'bg-white text-gray-500 hover:bg-gray-100'
                                                } border ${link.active ? 'border-indigo-600' : 'border-gray-300'}`}
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                    </div>
                                </nav>
                            </div>
                        )}
                    </div>

                    {students.length === 0 && (
                        <div className="text-center py-12">
                            <p className="text-gray-500">No students found.</p>
                        </div>
                    )}

                    {/* Credentials Modal */}
                    <CredentialsModal
                        show={showCredentialsModal}
                        credentials={allCredentials}
                        onClose={() => {
                            // Clear the client-side flash so credentials leave React state
                            // (the server session copy was already consumed by pull()).
                            router.flash({
                                credentials: null,
                                bulk_credentials: null,
                                bulk_failed: null,
                            });
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