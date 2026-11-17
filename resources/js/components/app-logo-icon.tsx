import type { SVGAttributes } from 'react';

/** The Lexnora mark: headphones with a text cursor (listen, then type). */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 22a14 14 0 0 1 28 0h-3.5a10.5 10.5 0 0 0-21 0z" />
            <rect x="5" y="22" width="8" height="13" rx="3" />
            <rect x="27" y="22" width="8" height="13" rx="3" />
            <rect x="18.8" y="25" width="2.4" height="9" rx="1.2" />
        </svg>
    );
}
