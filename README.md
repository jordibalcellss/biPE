# biPE

A timesheet and project reporting web application.

## Description

biPE is a prototype for a tailored business intelligence application.

At the moment it allows recording time spent on tasks and non-working
activities and comparing the resources spent (time and budget) against
the intended rates for such tasks. It was created as an approach to fulfill
the needs of small cooperative teams.

## Getting Started

### Dependencies

* An LDAP directory (OpenLDAP for instance)
* A webserver
* A MySQL/MariaDB database
* PHP 7 plus modules
  * php-mbstring
  * php-ldap
  * php-intl

### Installing

Pushing `database.sql` to MariaDB

```
mysql < database.sql
```

The configuration keys in regards of the database connection can be found
in `config.php`, MariaDB section.

### Configuration

`config.php` allows configuring as per the below settings, amongst others:

| Setting | Description |
| - | - |
| `MODE` | Switches from *bright* to *dark* CSS style |
| `LOGGING` | Activates a pair of access logfiles under *log/* |
| `LDAP_AUTH_*_GROUP` | Establishes three different roles |

The software can be easily localised with language files under *locale/*.

## Screenshots

![Log time](/screenshots/log.png?raw=true "Log time")
![My timesheet](/screenshots/timesheet.png?raw=true "My timesheet")
![Tasks](/screenshots/tasks.png?raw=true "Tasks")
![Task status](/screenshots/status.png?raw=true "Task status")

## License

This project is licensed under the GNU General Public License - see the
LICENSE file for details.
