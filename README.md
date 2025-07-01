# Video Editor App (Laravel + Vue.js)

## Requirements

-   PHP 8.1+
-   Composer
-   Node.js & npm
-   MySQL or compatible database
-   [FFmpeg](https://ffmpeg.org/) (binaries provided in `ffmpeg/` folder for Windows)

## Setup Instructions

1. **Clone the repository:**
    ```bash
    git clone https://github.com/MoizYounis/funsol-task.git
    cd funsol-task
    ```
2. **Install PHP dependencies:**
    ```bash
    composer install
    ```
3. **Install JS dependencies:**
    ```bash
    npm install
    ```
4. **Run migrations:**
    ```bash
    php artisan migrate
    ```
5. **Build frontend assets:**
    ```bash
    npm run build
    ```

## .env Setup

1. Copy the example file:
    ```bash
    cp .env.example .env
    ```
2. Generate app key:
    ```bash
    php artisan key:generate
    ```
3. Set your database credentials and other environment variables in `.env`.
4. Set up storage link:
    ```bash
    php artisan storage:link
    ```

## FFmpeg Setup

-   The `ffmpeg/` folder contains `ffmpeg.exe` and `ffprobe.exe` for Windows users.
-   You can use your own FFmpeg binaries if preferred.
-   **Add the `ffmpeg/` folder to your system PATH** or set the following in your `.env` file:

```
FFMPEG_BINARIES="E:/funsol-task/ffmpeg/ffmpeg.exe"
FFPROBE_BINARIES="E:/funsol-task/ffmpeg/ffprobe.exe"
```

-   Adjust the path as needed for your system.

## Features & Implementation

-   **User Authentication**: Secure login/register with Laravel Breeze.
-   **Video Projects**: Create, edit, and delete video projects.
-   **Video Upload**: Upload up to 4 videos per project (max 100MB each, mp4/avi/mov/wmv/flv/webm).
-   **Trim Videos**: Set start and end times for each video (optional).
-   **Merge & Export**: Merge all project videos (with trims) into one, using FFmpeg.
-   **Text Overlay**: Add custom text overlay to the exported video.
-   **Progress Bars**: Real-time progress for uploads and export.
-   **Download**: Download the final exported video when ready.
-   **Private Storage**: All videos and exports are stored securely (not public).
-   **Streaming**: Videos are streamed to the frontend via secure controller routes.
-   **CSRF Protection**: All forms and API calls are CSRF-protected.
-   **Validation**: All forms have proper validation and user feedback.

## Notes

-   Make sure FFmpeg is accessible to PHP (either via PATH or `.env` as above).
-   If you move the project folder, update the `.env` paths accordingly.
-   For production, use a queue for video exports for better performance.
-   For any issues, check the Laravel logs in `storage/logs/`.

---

**Enjoy your video editing app!**
