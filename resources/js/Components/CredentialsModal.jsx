import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';

export default function CredentialsModal({ show, credentials, onClose }) {
    const [copiedIndex, setCopiedIndex] = useState(null);

    if (!show || !credentials.length) return null;

    const handlePrint = () => {
        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        const rows = credentials.map((c, i) => `
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;">${i + 1}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${c.student_id}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${c.name || ''}</td>
                <td style="border: 1px solid #ddd; padding: 8px;">${c.user_id}</td>
                <td style="border: 1px solid #ddd; padding: 8px; font-family: monospace;">${c.temporary_password}</td>
            </tr>
        `).join('');

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Student Account Credentials</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { text-align: left; }
                    th { background: #f3f4f6; }
                    h1 { color: #1f2937; }
                    .warning { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <h1>Student Account Credentials</h1>
                <div class="warning">
                    <strong>⚠️ IMPORTANT:</strong> These are temporary passwords. Students must change their password on first login.
                    Do not share this document insecurely. Print and distribute securely.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 8px;">#</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Student ID</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Name</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">User ID</th>
                            <th style="border: 1px solid #ddd; padding: 8px;">Temporary Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
                <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">
                    Generated on ${new Date().toLocaleString()}
                </p>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    };

    const handleDownloadCSV = () => {
        const headers = ['#', 'Student ID', 'Name', 'User ID', 'Temporary Password'];
        const rows = credentials.map((c, i) => [
            i + 1,
            c.student_id,
            c.name || '',
            c.user_id,
            c.temporary_password,
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `student-credentials-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

    const copyToClipboard = (text, index) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopiedIndex(index);
            setTimeout(() => setCopiedIndex(null), 2000);
        });
    };

    return (
        <Modal show={show} maxWidth="3xl" onClose={onClose}>
            <div className="px-6 py-4">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-semibold text-gray-900">Account Credentials</h2>
                    <div className="flex items-center space-x-2">
                        <SecondaryButton onClick={handlePrint}>Print</SecondaryButton>
                        <PrimaryButton onClick={handleDownloadCSV}>Download CSV</PrimaryButton>
                    </div>
                </div>

                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ms-3">
                            <h3 className="text-sm font-medium text-yellow-800">Important Security Notice</h3>
                            <div className="mt-2 text-sm text-yellow-700">
                                <ul className="list-disc list-inside space-y-1">
                                    <li>These are <strong>temporary passwords</strong>. Students must change their password on first login.</li>
                                    <li>Distribute these credentials <strong>securely</strong> (e.g., sealed envelope, in-person).</li>
                                    <li>Do not email or share passwords through insecure channels.</li>
                                    <li>Each password is shown <strong>only once</strong> — save or print now.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temporary Password</th>
                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {credentials.map((cred, index) => (
                                <tr key={index} className="hover:bg-gray-50">
                                    <td className="px-4 py-4 text-sm text-gray-900">{index + 1}</td>
                                    <td className="px-4 py-4 text-sm text-gray-900 font-mono">{cred.student_id}</td>
                                    <td className="px-4 py-4 text-sm text-gray-900">{cred.name || '—'}</td>
                                    <td className="px-4 py-4 text-sm text-gray-900 font-mono">{cred.user_id}</td>
                                    <td className="px-4 py-4 text-sm text-gray-900 font-mono">{cred.temporary_password}</td>
                                    <td className="px-4 py-4">
                                        <button
                                            onClick={() => copyToClipboard(cred.temporary_password, index)}
                                            className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                            title="Copy password"
                                        >
                                            {copiedIndex === index ? 'Copied!' : 'Copy'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {credentials.length === 1 && (
                    <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                        <p className="text-sm text-gray-700">
                            <strong>Single credential generated.</strong> Provide this to the student securely.
                            They will be forced to change their password on first login.
                        </p>
                    </div>
                )}
            </div>
        </Modal>
    );
}