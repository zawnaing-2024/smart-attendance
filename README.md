# Smart Attendance System

A comprehensive attendance management system with three portals: Admin, Teacher, and Live Portal. Features CCTV integration, face detection, automatic SMS notifications, and real-time attendance tracking.

## Features

### Admin Portal
- **CCTV Camera Management**: Add, edit, and manage network cameras for live monitoring
- **Student Management**: Add students with roll numbers, grades, parent contacts, and face images
- **Grade Management**: Create and manage different grades/classes
- **Teacher Management**: Create teacher accounts and assign them to specific grades
- **SMS Settings**: Configure SMS provider settings for parent notifications

### Teacher Portal
- **Student Information**: View and manage assigned students
- **Attendance Lists**: Track daily attendance with manual override capabilities
- **Reports & Analytics**: Generate attendance reports with charts and statistics
- **Parent Communication**: Contact parents via phone or SMS

### Live Portal
- **Real-time Face Detection**: Monitor CCTV feeds with face recognition
- **Automatic Attendance**: System automatically marks attendance when students are detected
- **Live Statistics**: View real-time attendance statistics
- **Recent Activity**: Track recent check-ins and check-outs

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Installation

### 1. Database Setup

1. Create a MySQL database named `smart_attendance`
2. Import the database schema:
   ```bash
   mysql -u username -p smart_attendance < database/schema.sql
   ```

### 2. Configuration

1. Update database configuration in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'smart_attendance');
   ```

2. Create uploads directory for face images:
   ```bash
   mkdir uploads/faces
   chmod 777 uploads/faces
   ```

### 3. Web Server Setup

1. Place all files in your web server directory
2. Ensure PHP has write permissions to the uploads directory
3. Configure your web server to serve PHP files

### 4. Default Login Credentials

- **Admin Account**:
  - Username: `admin`
  - Password: `admin123`

## Usage

### Admin Portal Access
1. Login with admin credentials
2. Navigate to Admin Panel
3. Configure cameras, students, grades, and teachers
4. Set up SMS settings for parent notifications

### Teacher Portal Access
1. Admin creates teacher accounts
2. Teachers login with their credentials
3. Access student information and attendance management
4. Generate reports and communicate with parents

### Live Portal Access
1. Accessible to all logged-in users
2. Select a camera from the sidebar
3. Start face detection to monitor attendance
4. View real-time statistics and recent activity

## SMS Integration

The system includes SMS functionality for parent notifications:

1. Configure SMS settings in Admin Panel
2. System automatically sends SMS when students check in/out
3. Teachers can manually send SMS to parents
4. SMS logs are maintained for tracking

### SMS Provider Setup
- Update SMS settings with your provider's API credentials
- Test SMS functionality using the test feature
- Enable/disable SMS notifications as needed

## Face Detection Integration

The system is designed to integrate with face detection services:

1. **Camera Setup**: Add network cameras with accessible URLs
2. **Face Detection**: Implement face recognition API integration
3. **Automatic Attendance**: System marks attendance when faces are recognized
4. **Manual Override**: Teachers can manually adjust attendance

### Face Detection Implementation
- The live portal includes placeholder code for face detection
- Integrate with services like:
  - OpenCV
  - Face++ API
  - Amazon Rekognition
  - Google Vision API

## File Structure

```
smart-attendance/
├── admin/                 # Admin portal files
│   ├── includes/         # Admin header/footer
│   ├── dashboard.php     # Admin dashboard
│   ├── cameras.php       # CCTV management
│   ├── students.php      # Student management
│   ├── grades.php        # Grade management
│   ├── teachers.php      # Teacher management
│   └── sms.php          # SMS settings
├── teacher/              # Teacher portal files
│   ├── includes/         # Teacher header/footer
│   ├── ajax/            # AJAX endpoints
│   ├── dashboard.php     # Teacher dashboard
│   ├── students.php      # Student management
│   ├── attendance.php   # Attendance management
│   └── reports.php      # Reports & analytics
├── live/                # Live portal files
│   └── live.php         # Live face detection
├── config/              # Configuration files
│   └── database.php     # Database configuration
├── includes/            # Common functions
│   └── functions.php    # Utility functions
├── database/            # Database files
│   └── schema.sql       # Database schema
├── assets/              # Static assets
│   └── css/            # Stylesheets
├── uploads/             # File uploads
│   └── faces/          # Face images
├── index.php            # Main dashboard
├── login.php            # Login page
└── logout.php           # Logout handler
```

## Security Features

- **User Authentication**: Secure login system with password hashing
- **Role-based Access**: Different access levels for admin and teachers
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Protection**: Prepared statements used throughout
- **Session Management**: Secure session handling

## Customization

### Adding New Features
1. Create new PHP files in appropriate directories
2. Update navigation menus in header files
3. Add database tables as needed
4. Update functions.php for new utilities

### Styling
- Modify `assets/css/style.css` for custom styling
- Bootstrap 5 framework used for responsive design
- Custom gradients and animations included

### SMS Provider Integration
- Update `send_sms()` function in `includes/functions.php`
- Add your SMS provider's API integration
- Test thoroughly before production use

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **File Upload Issues**
   - Check uploads directory permissions
   - Ensure PHP upload settings are configured
   - Verify file size limits

3. **SMS Not Working**
   - Check SMS provider credentials
   - Verify API endpoints
   - Test with valid phone numbers

4. **Face Detection Issues**
   - Ensure camera URLs are accessible
   - Check network connectivity
   - Verify face detection service integration

### Support

For technical support or feature requests, please contact the development team.

## License

This project is proprietary software. All rights reserved.

## Version History

- **v1.0.0**: Initial release with basic attendance management
- **v1.1.0**: Added SMS integration and face detection framework
- **v1.2.0**: Enhanced reporting and analytics features

---

**Note**: This system is designed for educational institutions and requires proper setup and configuration for production use. Ensure all security measures are in place before deployment.
# smart_attendance
