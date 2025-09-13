**SnappAPI – Online Ride Request System 🚖**

#### Overall System Diagram:

<img src="./Snap Clone.png" alt="SnappAPI" style="width:50%">

#### Project EER Diagram:
<img src="./eer Diagram.png" alt="EER diagram" style="width:50%">

#### Project SQL Tables:
<img src="./drawSQL.png" alt="EER diagram" style="width:50%">


SnappAPI is an online taxi request system developed using Laravel 12. This project enables users to request rides, manage trip statuses, and facilitates interaction between passengers and drivers.

---

### 📌 **Project Workflow**

1. **Request a Ride**: Each user can submit a ride request using the `store` method.
    
2. **Request Processing**: The ride request is processed by a **Listener**, which calculates the distance between the origin and destination. Based on this distance, the **trip cost** is calculated.
    
3. **Broadcast to Drivers**: Ride details (origin, destination, distance, and price) are broadcasted via the `driver` **channel** to the nearest available drivers.
    
4. **Driver Accepts Request**: If a driver accepts the request using the `accept` method, the driver's and request information is broadcasted on the `users` **channel**.
    
5. **Track Ride Status**: Users can check their current trip status with the `status` method (e.g., whether the driver has arrived).  
    Drivers can also monitor their current status using the `status` method.
    
6. **Complete Ride**: Once the ride finishes, the driver signals completion with the `complete` method, and their status changes back to "available."
    

---

### 🔥 **Technologies Used**

- **Laravel 12** 🚀 (Primary backend framework)
    
- **Laravel Sanctum** 🔐 (User and driver authentication)
    
- **Redis** ⚡ (Caching and performance optimization)
    
- **Laravel Reverb** 📡 (Event broadcasting and real-time communication)
    
- **SQLite** 🛢️ (Primary database)
    
- **l5-swagger** 📜 (API documentation)
    
- **PhpUnit** 🧪 (Code quality testing)
    

---

### 🔧 Installation & Setup

1. Clone the project:
    

```bash
git clone git@github.com:pouria-azad/SnappAPI.git
cd SnappAPI
```

2. Install dependencies:
    

```bash
composer install
npm install
```

3. Configure `.env` file with database details:
    

```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations and seed database:
    

```bash
php artisan migrate --seed
```

5. Generate API documentation:
    

```bash
php artisan l5-swagger:generate
```

6. Start Redis for optimized performance:
    

```bash
redis-server
```

7. Run the project:
    

```bash
php artisan serve
php artisan reverb:start
php artisan queue:work
```

---

### 🛠️ **Key Features**

###### **Concurrent Request Limitations:**

⏳ A user cannot submit a new request until their current request is accepted or ongoing trip is completed.  
🚗 Similarly, drivers cannot accept new requests while on a trip.

###### **Improved Request Processing:**

🔄 Ride requests are currently processed via a **Listener**.  
📊 The database keeps track of drivers who have received the request. Future enhancements could use **Jobs** to manage request processing, gradually expanding the search radius for nearby drivers every few seconds—similar to Snapp.

###### **Token-based Authentication:**

🔑 All users (drivers and passengers) authenticate using **Sanctum tokens**.

###### **Complete Documentation:**

📜 All methods are fully documented, and **PHPUnit tests** cover functionality.

---

### 👨‍💻 **Developer**

[**Pouria Azad**](https://www.linkedin.com/in/pouria-azad)

---

### Contributors

### 👤 Amir Hossein Taghizadeh

- **Role:** Developer
    
- **GitHub:** [Amyrosein](https://github.com/Amyrosein)
    

---

📌 This project is designed as a clone of **Snapp / Uber**, with extensibility and customization in mind.

🚀 **Feel free to contribute or suggest improvements on GitHub!** 😎

---

## License

This project is licensed under the **GNU General Public License v3.0** — see the [LICENSE](https://chatgpt.com/LICENSE) file for details.
