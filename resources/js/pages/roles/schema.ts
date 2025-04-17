import { z } from "zod";

/**
 * Interface for Role entity
 * This represents a role in the system with its associated permissions
 */
export interface Role {
  id?: number;
  name: string;
  guard_name: string;
  permissions?: string[];
  permissions_count?: number;
  created_at?: string;
  updated_at?: string;
}

/**
 * Interface for RoleFormData used in forms
 * Similar to Role but with optional guard_name to match form state
 */
export interface RoleFormData {
  id?: number;
  name: string;
  guard_name?: string;
  permissions?: string[];
}

/**
 * Base schema for role validation
 * Contains the common fields and validation rules used by both create and update schemas
 */
const baseRoleSchema = z.object({
  name: z.string()
    .min(1, "El nombre del rol es obligatorio")
    .max(50, "El nombre del rol no puede exceder los 50 caracteres")
    .refine(value => /^[a-zA-Z0-9_-]+$/.test(value), {
      message: "El nombre del rol solo puede contener letras, números, guiones y guiones bajos"
    }),
  guard_name: z.string().default("web"),
  permissions: z.array(z.string()).default([]),
});

/**
 * Schema for creating a new role
 * Used for validating data when creating a new role
 */
export const createRoleSchema = baseRoleSchema;

/**
 * Schema for updating an existing role
 * Same as create schema in this case, but could be extended with specific validation for updates
 */
export const updateRoleSchema = baseRoleSchema;

// The RoleFormData type is already defined above as an interface
