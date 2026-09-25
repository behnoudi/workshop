# 🎓 AI Workshop & Interactive Learning Platform

An interactive, web-based educational platform designed for hosting workshops and self-paced lessons, featuring an integrated AI assistant powered by the **OpenRouter API**.

---

## ✨ Features

- 📚 **Modular Lesson Management:** Easily organize and render educational content from the `lessons/` directory.
- 🤖 **AI-Powered Learning Assistant:** Real-time conversational AI integration using large language models (LLMs) via OpenRouter.
- ⚡ **Lightweight Architecture:** Simple, efficient client-server communication using vanilla JavaScript and standard PHP backend endpoints.
- 🎨 **Responsive User Interface:** Clean, user-friendly layout styled with custom, modern CSS.
- 🔒 **Secure API Proxying:** Server-side API handling via `api.php` to protect sensitive requests and avoid CORS limitations.

---

## 📂 Project Structure

```text
├── lessons/       # Lesson content and workshop modules
├── api.js         # Client-side logic for prompt dispatch and UI updates
├── api.php        # Server-side proxy handling OpenRouter API requests
├── config.php     # Global configuration and API credentials
├── index.php      # Main application dashboard and lesson viewer
├── style.css      # Core styles and responsive UI design
└── README.md      # Project documentation
```

---

## ⚙️ Prerequisites

Before you begin, ensure you have the following installed on your machine:

- **PHP** >= 7.4 or 8.x (with the `curl` extension enabled)
- A local web server (e.g., Apache, Nginx, XAMPP, Laragon, or PHP's built-in development server)
- An active **[OpenRouter API Key](https://openrouter.ai/)**

---

## 🚀 Getting Started

### 1. Clone the Repository
```bash
git clone https://github.com/behnoudi/workshop.git
cd workshop
```

### 2. Configure Credentials
Open `config.php` in your editor and provide your OpenRouter API key:

```php
// config.php
define('OPENROUTER_API_KEY', 'your_openrouter_api_key_here');
```

> ⚠️ **Important Security Note:**  
> Never commit your real API key to a public Git repository. To prevent accidental leaks:
> - Keep production secrets in environment variables or a local `.env` file.
> - Ensure your private credentials file is listed in `.gitignore`.

### 3. Start the Development Server
You can launch the project instantly using PHP's built-in development server:

```bash
php -S localhost:8000
```

Open your browser and navigate to:
```text
http://localhost:8000
```

---

## 📖 How It Works

1. **Lesson Exploration:** The user selects a topic from the `lessons/` directory presented on `index.php`.
2. **AI Interaction:** Questions or interactive prompts entered by the learner are captured by `api.js`.
3. **Backend Dispatch:** `api.js` submits the request to `api.php`, which securely forwards the query to the OpenRouter API along with the required authentication headers.
4. **Response Delivery:** The model's response is returned and rendered dynamically within the user interface without requiring a full page reload.

---

## 📝 Adding New Lessons

To introduce new topics or learning modules:
1. Add your new content file into the `lessons/` directory.
2. Follow the existing template structure used by other lesson files.
3. The platform will automatically parse and display the updated topics in the interface.

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
```
