import { useForm, Head } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Link, router } from '@inertiajs/react';

export default function CreateStudent({ programs, can }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        student_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        suffix: '',
        sex: '',
        contact_number: '',
        year_level: '',
        program_id: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('registrar.students.store'));
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
            <Head title="Create Student" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-semibold text-gray-900">Create Student</h1>
                        <p className="mt-1 text-gray-600">Add a new student to the master list</p>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow space-y-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="student_id" value="Student ID" />
                                <TextInput
                                    id="student_id"
                                    type="text"
                                    value={data.student_id}
                                    onChange={(e) => setData('student_id', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="2025-00001"
                                    autoComplete="off"
                                />
                                <InputError message={errors.student_id} className="mt-1" />
                            </div>

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
                                Create Student
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}