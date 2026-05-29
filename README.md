# Free Fire Tournament Platform

## Overview
The Free Fire Tournament Platform is a web application designed for players to join and participate in Free Fire tournaments. It allows tournament creators to host and manage tournaments while providing an admin dashboard for overall management of the platform.

## Features
- **User Roles**: Supports three user roles - Player, Creator, and Admin.
- **Tournament Management**: Creators can create, manage, and view tournaments.
- **Player Participation**: Players can view, join, and track their tournaments.
- **Payment Integration**: Supports payment processing through Razorpay and Paytm.
- **Admin Dashboard**: Admins can manage users, tournaments, and view statistics.

## Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (Bootstrap 5 or TailwindCSS)
- **Backend**: PHP (procedural or mysqli)
- **Database**: MySQL
- **Hosting Compatibility**: InfinityFree, 000WebHost, Local XAMPP

## Database Structure
The application uses a MySQL database with the following tables:
- **users**: Stores user information and roles.
- **tournaments**: Contains details about tournaments.
- **registrations**: Tracks player registrations for tournaments.
- **payments**: Records payment transactions.
- **withdrawals**: Manages creator earnings withdrawals.
- **announcements**: Stores platform announcements.

## Installation
1. Clone the repository or download the source code.
2. Set up a MySQL database and import the schema from `src/sql/schema.sql`.
3. Configure database credentials in `src/config.php`.
4. Upload the files to your web server or run locally using XAMPP.
5. Access the application via your web browser.

## Usage
- **Players**: Sign up, log in, view tournaments, and join tournaments.
- **Creators**: Log in, create tournaments, manage participants, and withdraw earnings.
- **Admins**: Manage users, tournaments, and view overall statistics.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.