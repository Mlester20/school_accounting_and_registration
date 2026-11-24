import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function ConfirmActionModal({ show, action, student, status, onClose, onConfirm }) {
    if (!show || !action || !student) return null;

    const messages = {
        reset: {
            title: 'Reset Password',
            description: `Are you sure you want to reset the password for ${student.full_name} (${student.student_id})?`,
            details: 'A new temporary password will be generated. The student will be forced to change it on next login. All active sessions will be invalidated.',
            confirmText: 'Reset Password',
            variant: 'warning',
        },
        status: {
            title: status === 'inactive' ? 'Deactivate Account' : 'Activate Account',
            description: `Are you sure you want to ${status === 'inactive' ? 'deactivate' : 'activate'} the account for ${student.full_name} (${student.student_id})?`,
            details: status === 'inactive'
                ? 'The student will not be able to log in. All active sessions will be terminated.'
                : 'The student will be able to log in with their current password.',
            confirmText: status === 'inactive' ? 'Deactivate' : 'Activate',
            variant: status === 'inactive' ? 'danger' : 'success',
        },
    };

    const config = messages[action];

    return (
        <Modal show={show} maxWidth="md" onClose={onClose}>
            <div className="px-6 py-4">
                <h2 className="text-lg font-semibold text-gray-900 mb-2">{config.title}</h2>
                <p className="text-gray-600 mb-2">{config.description}</p>
                <p className="text-sm text-gray-500 mb-4">{config.details}</p>

                <div className="flex justify-end space-x-3">
                    <SecondaryButton onClick={onClose}>Cancel</SecondaryButton>
                    {config.variant === 'danger' && (
                        <DangerButton onClick={onConfirm}>{config.confirmText}</DangerButton>
                    )}
                    {config.variant === 'warning' && (
                        <PrimaryButton className="bg-yellow-600 hover:bg-yellow-700 border-yellow-600" onClick={onConfirm}>
                            {config.confirmText}
                        </PrimaryButton>
                    )}
                    {config.variant === 'success' && (
                        <PrimaryButton className="bg-green-600 hover:bg-green-700 border-green-600" onClick={onConfirm}>
                            {config.confirmText}
                        </PrimaryButton>
                    )}
                </div>
            </div>
        </Modal>
    );
}