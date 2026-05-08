# Sistema CSI - Agent Instructions

## Critical Conventions

### Database
- **Always use PDO** - never MySQLi
- **Table naming**: PascalCase (`Tickets`, `Usuarios`, etc.)
- When adding features: **add DB columns first via SQL**, then update PHP

### Frontend
- Load jQuery in `<head>` to avoid issues
- Body classes: `has-sidebar` (with menu), `no-sidebar` (without)
- Images from `imagen/` folder
- Menu files: `includes/menu_$privilegio.php` (auto-included by privilege)

### User Privileges
Roles: `admin`, `oati`, `infraestructura`, `usuario`, `director`, `bienes`  
**Always handle all roles** when adding functionality

## Key Files
- `crear_ticket.php` - Create ticket (admins/oati set custom date/priority)
- `procesar_ticket.php` - Process ticket (includes "Compartir con Bienes")
- `admin_usuarios.php` - User management
- `bandeja_bienes.php` - Shared tickets for Bienes role
- Dashboard stats: use hyperlinks to filtered views
- Filters: GET params (`?estado=Nuevo&pagina=1`)

## Database Changes
1. Add column: `ALTER TABLE Tickets ADD COLUMN ...`
2. Update privilege if needed: `ALTER TABLE Usuarios MODIFY privilegio VARCHAR(20)`
3. Insert new users with correct `privilegio` value

## Testing
Test features with **all 5 user types**: admin, tecnico, usuario, director, bienes

## Manuals
- `docs/ManualUsuario.html` - Regular user
- `docs/ManualOATI.html` - OATI user
- `docs/ManualAdministrador.html` - Admin
- `docs/ManualBienes.html` - Bienes user