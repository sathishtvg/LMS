import React from "react";

function cn(...xs) {
  return xs.filter(Boolean).join(" ");
}

export default function Button({
  variant = "primary", // primary|secondary|danger|ghost
  size = "md", // sm|md
  className = "",
  disabled = false,
  type = "button",
  children,
  ...props
}) {
  const base = "inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-200";
  const sizes = {
    sm: "px-3 py-2 text-xs",
    md: "px-4 py-2 text-sm",
  };
  const variants = {
    primary: "bg-blue-600 text-white hover:bg-blue-700",
    secondary: "border border-slate-200 bg-white text-slate-700 hover:bg-slate-50",
    danger: "bg-red-600 text-white hover:bg-red-700",
    ghost: "text-slate-700 hover:bg-slate-100",
  };

  return (
    <button
      type={type}
      disabled={disabled}
      className={cn(
        base,
        sizes[size] || sizes.md,
        variants[variant] || variants.primary,
        disabled ? "opacity-50 pointer-events-none" : "",
        className
      )}
      {...props}
    >
      {children}
    </button>
  );
}
