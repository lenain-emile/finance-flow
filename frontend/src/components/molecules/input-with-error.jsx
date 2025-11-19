import { Input } from "@/components/atoms/input"
import { Label } from "@/components/atoms/label"
import { cn } from "@/lib/utils"

export function InputWithError({
  id,
  name,
  label,
  type = "text",
  placeholder,
  value,
  onChange,
  onBlur,
  disabled = false,
  required = false,
  error,
  touched,
  className = "",
  ...props
}) {
  const hasError = error && touched
  
  return (
    <div className="form-field">
      <Label htmlFor={id} className="form-label">
        {label} {required && <span className="text-red-500">*</span>}
      </Label>
      <Input
        id={id}
        name={name}
        type={type}
        placeholder={placeholder}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        className={cn(
          "form-input",
          hasError && "form-input--error",
          className
        )}
        {...props}
      />
      {hasError && (
        <p className="form-error">{error}</p>
      )}
    </div>
  )
}