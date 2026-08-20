export default function ApplicationLogo({ className = '', alt = 'Logo Koperasi', ...props }) {
    return (
        <img
            src="/logo.png"
            alt={alt}
            className={`object-contain ${className}`}
            {...props}
        />
    );
}

