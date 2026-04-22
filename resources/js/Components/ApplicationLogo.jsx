export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
        >
            <defs>
                <linearGradient id="pdv-screen" x1="12" y1="10" x2="48" y2="42" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#22C55E" />
                    <stop offset="1" stopColor="#15803D" />
                </linearGradient>
                <linearGradient id="pdv-base" x1="20" y1="42" x2="44" y2="58" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#FB923C" />
                    <stop offset="1" stopColor="#EA580C" />
                </linearGradient>
            </defs>

            <rect x="10" y="8" width="44" height="30" rx="8" fill="url(#pdv-screen)" />
            <rect x="12.5" y="10.5" width="39" height="25" rx="6" stroke="#DCFCE7" strokeWidth="1.5" opacity="0.9" />

            <path
                d="M25 44H39"
                stroke="#166534"
                strokeWidth="3"
                strokeLinecap="round"
            />
            <path
                d="M22 52H42"
                stroke="url(#pdv-base)"
                strokeWidth="6"
                strokeLinecap="round"
            />
            <path
                d="M30 38V44"
                stroke="#166534"
                strokeWidth="3"
                strokeLinecap="round"
            />
            <path
                d="M34 38V44"
                stroke="#166534"
                strokeWidth="3"
                strokeLinecap="round"
            />

            <text
                x="32"
                y="29"
                textAnchor="middle"
                fontSize="13"
                fontWeight="800"
                fontFamily="Arial, sans-serif"
                fill="#FFFFFF"
                letterSpacing="1.2"
            >
                PDV
            </text>

            <circle cx="48" cy="16" r="4" fill="#FDBA74" />
            <path
                d="M48 13.5V18.5"
                stroke="#9A3412"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
            <path
                d="M45.5 16H50.5"
                stroke="#9A3412"
                strokeWidth="1.5"
                strokeLinecap="round"
            />
        </svg>
    );
}
