import { HTMLAttributes } from 'react';

export default function AppLogoIcon({ className, ...rest }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div 
            className={`flex items-center justify-center ${className}`} 
            {...rest}
        >
            <img 
                src="/favicon.ico?v=2" 
                alt="FVF Logo" 
                className="h-16 w-16 object-contain" 
                style={{ imageRendering: 'auto' }}
            />
        </div>
    );
}
