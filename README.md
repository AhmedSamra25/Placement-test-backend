# 🚀 Placement Test Platform - Backend

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![Gemini AI](https://img.shields.io/badge/AI-Gemini%201.5%20Pro-4285F4?style=for-the-badge&logo=google)](https://deepmind.google/technologies/gemini/)

A sophisticated, AI-enabled language placement platform designed to assess student English levels across multiple core skills. Using **Google Gemini 1.5 Pro**, this platform automates the grading process for complex tasks, providing detailed feedback and actionable insights for educational organizations.

---

## ✨ Key Features

- **🌐 Multi-Tenant Architecture**: Secure, isolated environments for different educational organizations (schools, universities, etc.).
- **🧠 7-Skill Assessment Engine**: Comprehensive testing for Reading, Writing, Listening, Speaking, Grammar, Vocabulary, and Use of English.
- **🤖 AI-Powered Grading Pipeline**: Automated evaluation of Writing and Speaking submissions using advanced LLM analysis (Gemini 1.5 Pro).
- **📊 Admin & Organization Dashboards**: Robust analytics and management tools for student cohorts, test results, and CEFR distributions.
- **🔒 Role-Based Access Control (RBAC)**: Fine-grained permissions for Admins, Organization Managers, and Students.
- **⚡ Asynchronous Processing**: Background jobs for high-performance AI analysis and result generation.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 11
- **Authentication**: Laravel Sanctum (Token-Based)
- **Database**: MySQL / PostgreSQL
- **AI Integration**: Google Gemini 1.5 Pro API via Laravel AI SDK
- **Task Queue**: Redis / Database driver for asynchronous AI grading
- **Media Support**: Integrated storage for audio recordings and student uploads

---

## 🚀 Quick Start Guide

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+
- A Google Cloud API Key (for Gemini AI)

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/placement-test-backend.git
   cd placement-test-backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   ```
   *Edit the `.env` file and configure your database, mail server, and AI credentials.*

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Database Migration & Seeding:**
   ```bash
   php artisan migrate --seed
   ```

6. **Storage Link:**
   ```bash
   php artisan storage:link
   ```

7. **Start the Development Server:**
   ```bash
   php artisan serve
   ```

8. **Start the Queue Worker (Crucial for AI Grading):**
   ```bash
   php artisan queue:work
   ```

---

## 📋 API Documentation

This platform follows standard RESTful principles. Key endpoints include:

- `POST /api/login` - User authentication
- `GET /api/org/dashboard` - Organization analytics
- `POST /api/test/submit` - Student test submission
- `GET /api/test/results/{testId}` - Detailed skill-by-skill breakdown

---

## 🏗️ Core Architecture Components

### Models
- **Organization**: Manages institutions and their associated students/admins.
- **User**: Core identity with roles (Admin, Student, Org-Manager).
- **TestSubmission**: Tracks student attempts and manages the grading state.
- **SkillScore**: Stores granular scores per skill (0-100) and CEFR levels.

### Jobs & AI Pipeline
- **AnalyzeTestSubmission**: The heavy-lifting background job that sends content to Gemini AI and processes the JSON response.

---

## 🤝 Contributing

We welcome contributions! Please feel free to submit Pull Requests or open issues for feature requests.

---

## 📄 License

This software is licensed under the [MIT License](LICENSE).

---

<p align="center">Built with ❤️ for better language learning experiences.</p>
