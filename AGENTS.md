# Sistema CSI - Agent Instructions

## Project Overview
PHP-based ticket management system (Centro de Soporte Informático) for handling IT support requests.

## Critical Conventions

### Database
- **Always use PDO** - never MySQLi
- **Table naming**: PascalCase (`Tickets`, `Usuarios`, `Dependencias`, `AreasSoporte`, `Servicios`)
- When adding new features, add database columns first via SQL

### Frontend
- Load jQuery in `<head>` to avoid loading issues
- Use images from `imagen/` folder
- Menus must be unified with same style for all user types

### User Privileges
Available roles: `admin`, `tecnico`, `usuario`, `director`, `bienes`

When adding new functionality, handle all these roles appropriately.

## Key Files

### Menu Files
- `includes/menu_admin.php` - Admin menu
- `includes/menu_tecnico.php` - Technician menu  
- `includes/menu_usuario.php` - Regular user menu
- `includes/menu_director.php` - Director menu
- `includes/menu_bienes.php` - Bienes (inventory) user menu

### Important Pages
- `crear_ticket.php` - Create ticket (technicians/admins can set custom date, priority)
- `procesar_ticket.php` - Process ticket (includes "Compartir con Bienes" option)
- `admin_usuarios.php` - User management
- `bandeja_bienes.php` - Shared tickets for Bienes role

### Manuals
- `docs/ManualUsuario.html` - Regular user manual
- `docs/ManualTecnico.html` - Technician manual
- `docs/ManualAdministrador.html` - Admin manual
- `docs/ManualBienes.html` - Bienes user manual

## Database Changes Required

When adding new features that need database changes:
1. Add column via SQL: `ALTER TABLE Tickets ADD COLUMN ...`
2. Modify user privilege: `ALTER TABLE Usuarios MODIFY privilegio VARCHAR(20)`
3. Insert new users with correct privilegio value

## Common Development Patterns

- Menu files auto-include based on privilege: `includes/menu_$privilegio.php`
- Statistics in dashboards use hyperlinks to filtered views
- Filters use GET parameters (`?estado=Nuevo&pagina=1`)

## Testing
Test new features with all user types: admin, tecnico, usuario, director, bienes
