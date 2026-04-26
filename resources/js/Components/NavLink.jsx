import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    variant = 'default',
    children,
    ...props
}) {
    const baseClassName =
        variant === 'top-control'
            ? 'inline-flex items-center gap-2 rounded-full border border-sky-500 bg-sky-500 px-4 py-2 text-sm font-semibold leading-5 text-white shadow-sm transition duration-150 ease-in-out hover:border-sky-600 hover:bg-sky-600 focus:outline-none dark:border-sky-300 dark:bg-sky-300 dark:text-sky-950 dark:hover:border-sky-200 dark:hover:bg-sky-200'
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
