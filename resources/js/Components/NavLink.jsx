import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    variant = 'default',
    tone = 'Info',
    children,
    ...props
}) {
    const topControlToneClassMap = {
        Default:
            'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-700',
        Primary:
            'border-blue-500 bg-blue-500 text-white hover:border-blue-600 hover:bg-blue-600 dark:border-blue-300 dark:bg-blue-300 dark:text-blue-950 dark:hover:border-blue-200 dark:hover:bg-blue-200',
        Secondary:
            'border-fuchsia-500 bg-fuchsia-500 text-white hover:border-fuchsia-600 hover:bg-fuchsia-600 dark:border-fuchsia-300 dark:bg-fuchsia-300 dark:text-fuchsia-950 dark:hover:border-fuchsia-200 dark:hover:bg-fuchsia-200',
        Info:
            'border-cyan-500 bg-cyan-500 text-white hover:border-cyan-600 hover:bg-cyan-600 dark:border-cyan-300 dark:bg-cyan-300 dark:text-cyan-950 dark:hover:border-cyan-200 dark:hover:bg-cyan-200',
        Success:
            'border-green-500 bg-green-500 text-white hover:border-green-600 hover:bg-green-600 dark:border-green-300 dark:bg-green-300 dark:text-green-950 dark:hover:border-green-200 dark:hover:bg-green-200',
        Warning:
            'border-orange-500 bg-orange-500 text-white hover:border-orange-600 hover:bg-orange-600 dark:border-orange-300 dark:bg-orange-300 dark:text-orange-950 dark:hover:border-orange-200 dark:hover:bg-orange-200',
        Error:
            'border-red-500 bg-red-500 text-white hover:border-red-600 hover:bg-red-600 dark:border-red-300 dark:bg-red-300 dark:text-red-950 dark:hover:border-red-200 dark:hover:bg-red-200',
        Dark:
            'border-slate-800 bg-slate-800 text-white hover:border-slate-900 hover:bg-slate-900 dark:border-slate-200 dark:bg-slate-200 dark:text-slate-950 dark:hover:border-slate-100 dark:hover:bg-slate-100',
        Light:
            'border-slate-200 bg-slate-100 text-slate-700 hover:border-slate-300 hover:bg-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:hover:border-slate-500 dark:hover:bg-slate-600',
    };

    const baseClassName =
        variant === 'top-control'
            ? `inline-flex items-center gap-2 rounded-full border px-2 py-1 text-sm font-semibold leading-5 shadow-sm transition duration-150 ease-in-out focus:outline-none ${
                  topControlToneClassMap[tone] ?? topControlToneClassMap.Info
              }`
            : 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none';

    const stateClassName =
        variant === 'top-control'
            ? active
                ? ' ring-2 ring-sky-200 ring-offset-2 dark:ring-sky-900/40 dark:ring-offset-gray-800'
                : ''
            : active
                ? 'border-indigo-400 text-gray-900 focus:border-indigo-700 dark:border-indigo-600 dark:text-gray-100'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300 dark:focus:border-gray-700 dark:focus:text-gray-300';

    return (
        <Link
            {...props}
            className={`${baseClassName} ${stateClassName}${className ? ` ${className}` : ''}`}
        >
            {children}
        </Link>
    );
}
