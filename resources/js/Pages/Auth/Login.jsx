import PrimaryButton from '@/Components/Button/PrimaryButton';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const normalizeNumericPassword = (value) => value.replace(/\D/g, '');

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Login" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mx-auto w-full max-w-md">
                <div>
                    <InputLabel htmlFor="email" value="E-mail" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        placeholder="seuemail@provedor.com"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="email"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Senha" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Digite sua senha numerica"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        inputMode="numeric"
                        pattern="[0-9]*"
                        onChange={(e) => setData('password', normalizeNumericPassword(e.target.value))}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600 dark:text-gray-400">
                            Lembrar
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    {canResetPassword ? (
                        <Link
                            href={route('password.request')}
                            className="no-underline rounded-md text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                        >
                            Esqueceu a senha?
                        </Link>
                    ) : (
                        <span />
                    )}

                    <PrimaryButton disabled={processing}>
                        LOGIN
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
