# Food Ordering System Setup Instructions

## Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

## Installation Steps

### 1. Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the database schema:
   - Create a new database called `food_ordering`
   - Import the `database.sql` file or run the SQL commands manually

### 2. File Setup
1. Place all files in `c:\xampp\htdocs\food_ordering\`
2. Ensure all directories have proper permissions

### 3. Database Configuration
- The database connection settings are in `includes/db.php`
- Default settings:
  - Host: localhost
  - Username: root
  - Password: (empty)
  - Database: food_ordering

### 4. Default Admin Account
- Username: admin
- Password: admin123
- Email: admin@foodsystem.com

## Features

### Admin Features
- Dashboard with statistics
- Manage menu items (add, edit, delete)
- Manage branches (add, edit, delete)
- Manage users and assign roles
- View all orders across branches

### Branch Moderator Features
- Branch-specific dashboard
- View and manage orders for their branch
- Update order status (pending → confirmed → preparing → out for delivery → delivered)

### User Features
- Browse menu by categories
- Add items to cart with quantity selection
- Place orders with delivery address
- Automatic nearest branch calculation
- View order history and status
- Cash on delivery payment

### Technical Features
- Responsive design
- Role-based access control
- Session management
- Input validation and sanitization
- Distance calculation for nearest branch
- Local storage for shopping cart
- Clean, modular code structure

## File Structure
```
/food_ordering/
├── /admin/              # Admin panel pages
├── /branch/             # Branch moderator pages
├── /user/               # User pages
├── /includes/           # Shared PHP includes
├── /assets/             # CSS, JS, and other assets
├── index.php            # Landing page
├── login.php            # Login page
├── register.php         # Registration page
├── logout.php           # Logout script
└── database.sql         # Database schema
```

## Usage

### For Admins
1. Login with admin credentials
2. Access admin dashboard
3. Manage menu items, branches, and users
4. Monitor orders across all branches

### For Branch Moderators
1. Admin creates branch moderator account and assigns to a branch
2. Login and access branch dashboard
3. View and manage orders for their specific branch
4. Update order status as orders progress

### For Regular Users
1. Register new account or login
2. Browse menu and add items to cart
3. Proceed to checkout and enter delivery address
4. System automatically finds nearest branch
5. Place order with cash on delivery
6. Track order status in "My Orders"

## Customization

### Adding New Menu Categories
- Simply add menu items with new category names
- Categories are automatically grouped in the menu display

### Adding New Branches
- Use admin panel to add branches with coordinates
- System automatically calculates distances for order routing

### Modifying Order Status Flow
- Edit the status options in `includes/functions.php`
- Update status badge classes in CSS as needed

## Security Notes
- All user inputs are sanitized
- Passwords are hashed using PHP's password_hash()
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements

## Troubleshooting

### Common Issues
1. **Database connection fails**: Check XAMPP MySQL is running and credentials in db.php
2. **Pages show errors**: Ensure PHP error reporting is enabled for debugging
3. **Styles not loading**: Check file paths in header.php
4. **Location features not working**: Ensure HTTPS for geolocation or use localhost

### Browser Console Errors
- Check browser console for JavaScript errors
- Ensure all JS files are loading correctly

## Future Enhancements
- Real-time order tracking
- Email notifications
- SMS notifications
- Online payment integration
- Mobile app
- Advanced reporting and analytics
- Inventory management
- Customer reviews and ratings
