import { useForm, Head } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Link, router } from '@inertiajs/react';

export default function EditStudent({ student, programs, can }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        first_name: student.first_name,
        middle_name: student.middle_name || '',
        last_name: student.last_name,
        suffix: student.suffix || '',
        sex: student.sex,
        contact_number: student.contact_number || '',
        year_level: student.year_level,
        program_id: student.program?.id || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('registrar.students.update', student.id));
    };

    const handleCancel = () => {
        router.visit(route('registrar.students.index'));
    };

    const sexOptions = [
        { value: 'male', label: 'Male' },
        { value: 'female', label: 'Female' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Edit Student" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-semibold text-gray-900">Edit Student</h1>
                        <p className="mt-1 text-gray-600">Update student information</p>
                    </div>

                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ms-3">
                                <h3 className="text-sm font-medium text-yellow-800">Student ID cannot be changed</h3>
                                <div className="mt-2 text-sm text-yellow-700">
                                    <p>Student ID: <span className="font-mono font-medium">{student.student_id}</span></p>
                                    <p>This is a permanent identifier and cannot be modified.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow space-y-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="program_id" value="Program" />
                                <select
                                    id="program_id"
                                    value={data.program_id}
                                    onChange={(e) => setData('program_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                                    required
                                >
                                    <option value="">Select Program</option>
                                    {programs.map(p => (
                                        <option key={p.id} value={p.id}>{p.code} - {p.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.program_id} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <InputLabel htmlFor="first_name" value="First Name" />
                                <TextInput
                                    id="first_name"
                                    value={data.first_name}
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                    autoComplete="given-name"
                                />
                                <InputError message={errors.first_name} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="middle_name" value="Middle Name" />
                                <TextInput
                                    id="middle_name"
                                    value={data.middle_name}
                                    onChange={(e) => setData('middle_name', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="additional-name"
                                />
                                <InputError message={errors.middle_name} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="last_name" value="Last Name" />
                                <TextInput
                                    id="last_name"
                                    value={data.last_name}
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                    autoComplete="family-name"
                                />
                                <InputError message={errors.last_name} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="suffix" value="Suffix" />
                                <TextInput
                                    id="suffix"
                                    value={data.suffix}
                                    onChange={(e) => setData('suffix', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Jr., Sr., III, etc."
                                />
                                <InputError message={errors.suffix} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="sex" value="Sex" />
                                <select
                                    id="sex"
                                    value={data.sex}
                                    onChange={(e) => setData('sex', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
                                    required
                                >
                                    <option value="">Select Sex</option>
                                    {sexOptions.map(opt => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                                <InputError message={errors.sex} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="contact_number" value="Contact Number" />
                                <TextInput
                                    id="contact_number"
                                    type="tel"
                                    value={data.contact_number}
                                    onChange={(e) => setData('contact_number', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="09123456789"
                                    autoComplete="tel"
                                />
                                <InputError message={errors.contact_number} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="year_level" value="Year Level" />
                                <TextInput
                                    id="year_level"
                                    type="number"
                                    value={data.year_level}
                                    onChange={(e) => setData('year_level', e.target.value)}
                                    className="mt-1 block w-full"
                                    min="1"
                                    max="6"
                                    required
                                />
                                <InputError message={errors.year_level} className="mt-1" />
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link href={route('registrar.students.index')}>
                                <SecondaryButton type="button" onClick={handleCancel}>Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton type="submit" disabled={processing}>
                                Update Student
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}