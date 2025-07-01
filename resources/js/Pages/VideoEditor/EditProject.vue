<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ form.name }}
                </h2>
                <div class="flex space-x-2">
                    <Link :href="route('video-editor.projects')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Projects
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Project Edit Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="updateProject" class="space-y-4 max-w-xl">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Project Name
                                    <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.name }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="form.description"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    rows="2"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.description }}
                                </div>
                            </div>
                            <button type="submit" :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Save
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Project Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium">
                                    {{ form.name }}
                                </h3>
                                <p v-if="form.description" class="text-gray-600">
                                    {{ form.description }}
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Videos: {{ project.videos.length }}/4 |
                                    Status:
                                    <span :class="{
                                        'text-green-600':
                                            project.status === 'completed',
                                        'text-yellow-600':
                                            project.status === 'processing',
                                        'text-red-600':
                                            project.status === 'failed',
                                        'text-gray-600':
                                            project.status === 'draft',
                                    }">
                                        {{ project.status }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button v-if="
                                    project.videos.length >= 4 &&
                                    project.status !== 'processing'
                                " @click="exportVideo" :disabled="exporting"
                                    class="bg-green-500 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded">
                                    {{
                                        exporting
                                            ? "Exporting..."
                                            : "Export Video"
                                    }}
                                </button>
                                <button v-if="project.status === 'completed'" @click="downloadVideo"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Upload -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Upload Videos</h3>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <input ref="fileInput" type="file" accept="video/*" multiple @change="handleFileUpload"
                                class="hidden" />
                            <button @click="$refs.fileInput.click()" :class="[
                                'font-bold py-2 px-4 rounded',
                                uploading || project.videos.length >= 4
                                    ? 'bg-gray-200 text-gray-500 cursor-default'
                                    : 'bg-blue-500 hover:bg-blue-700 text-white',
                            ]" :disabled="uploading || project.videos.length >= 4
                                    " :title="project.videos.length >= 4
                                        ? 'You can only add up to 4 videos per project.'
                                        : ''
                                    ">
                                {{
                                    uploading ? "Uploading..." : "Select Videos"
                                }}
                            </button>
                            <p class="text-sm text-gray-500 mt-2">
                                Supported formats: MP4, AVI, MOV, WMV, FLV, WebM
                                (Max 100MB each)
                            </p>
                        </div>
                        <!-- Upload Progress Bar -->
                        <div v-if="uploading" class="mt-4 w-full">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Uploading: {{ currentUploadingFile }}</span>
                                <span>{{ uploadProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: uploadProgress + '%' }"></div>
                            </div>

                            <!-- Overall progress for multiple files -->
                            <div v-if="Object.keys(uploadProgressMap).length > 1" class="mt-3">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Overall Progress</span>
                                    <span>{{ getOverallUploadProgress() }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" :style="{
                                        width:
                                            getOverallUploadProgress() +
                                            '%',
                                    }"></div>
                                </div>
                            </div>

                            <!-- Multiple file progress indicators -->
                            <div v-if="Object.keys(uploadProgressMap).length > 1" class="mt-3 space-y-2">
                                <div v-for="(
progress, fileName
                                    ) in uploadProgressMap" :key="fileName" class="text-xs">
                                    <div class="flex justify-between text-gray-500 mb-1">
                                        <span class="truncate">{{
                                            fileName
                                            }}</span>
                                        <span>{{ progress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1">
                                        <div class="bg-blue-400 h-1 rounded-full transition-all duration-300"
                                            :style="{ width: progress + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video List -->
                <div v-if="project.videos.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">
                            Videos ({{ project.videos.length }}/4)
                        </h3>

                        <div class="space-y-4">
                            <div v-for="video in project.videos" :key="video.id" class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="font-medium">
                                            {{ video.original_filename }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            Duration:
                                            {{ formatDuration(video.duration) }}
                                            | Size:
                                            {{
                                                formatFileSize(video.file_size)
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button @click="deleteVideo(video)"
                                            class="bg-red-500 hover:bg-red-700 text-white text-sm py-1 px-3 rounded">
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <!-- Video Player -->
                                <div class="mb-4">
                                    <video :src="video.public_url" controls class="w-full max-w-md rounded"
                                        @loadedmetadata="
                                            onVideoLoaded($event, video)
                                            "></video>
                                </div>

                                <!-- Trim Controls -->
                                <div class="space-y-3">
                                    <h5 class="font-medium">Trim Video</h5>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Start Time
                                                (seconds)</label>
                                            <input type="number" v-model="video.start_time" min="0"
                                                :max="video.duration"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                @change="
                                                    onStartTimeChange(video)
                                                    " />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">End Time
                                                (seconds)</label>
                                            <input type="number" v-model="video.end_time" :min="video.start_time"
                                                :max="video.duration"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                @change="updateVideo(video)" />
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        Trimmed duration:
                                        {{
                                            formatDuration(
                                                getTrimmedDuration(video)
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Text Overlay Settings -->
                <div v-if="project.videos.length >= 4" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4">Text Overlay</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Custom Text</label>
                            <input v-model="textOverlay" type="text"
                                placeholder="Enter text to overlay on the final video"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-xs text-gray-500 mt-1">
                                Enter the text you want to appear on the
                                exported video. Please enter your overlay text
                                before exporting.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useForm, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    project: Object,
});

const form = useForm({
    name: props.project.name,
    description: props.project.description,
});

const fileInput = ref(null);
const uploading = ref(false);
const exporting = ref(false);
const exportProgress = ref(0);
const uploadProgress = ref(0);
const textOverlay = ref("Funsol Technologies");
const currentUploadingFile = ref(null);
const uploadProgressMap = ref({});

const handleFileUpload = async (event) => {
    console.log("File input changed", event.target.files);
    const files = Array.from(event.target.files);
    uploading.value = true;
    uploadProgress.value = 0;
    uploadProgressMap.value = {};

    for (const file of files) {
        if (props.project.videos.length >= 4) break;

        currentUploadingFile.value = file.name;
        uploadProgressMap.value[file.name] = 0;

        const formData = new FormData();
        formData.append("video", file);

        try {
            // Use XMLHttpRequest to track upload progress
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener("progress", (e) => {
                if (e.lengthComputable) {
                    const progress = Math.round((e.loaded / e.total) * 100);
                    uploadProgressMap.value[file.name] = progress;
                    uploadProgress.value = progress;
                }
            });

            xhr.addEventListener("load", () => {
                if (xhr.status === 200) {
                    const result = JSON.parse(xhr.responseText);
                    props.project.videos.push(result.video);
                    toastr.success("Video uploaded successfully");
                    delete uploadProgressMap.value[file.name];
                } else {
                    toastr.error("Upload failed");
                    delete uploadProgressMap.value[file.name];
                }
            });

            xhr.addEventListener("error", () => {
                toastr.error("Upload failed due to an error");
                delete uploadProgressMap.value[file.name];
            });

            xhr.open(
                "POST",
                route("video-editor.videos.store", props.project.id)
            );
            xhr.setRequestHeader(
                "X-CSRF-TOKEN",
                document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content")
            );
            xhr.send(formData);

            // Wait for upload to complete before proceeding to next file
            await new Promise((resolve, reject) => {
                xhr.addEventListener("load", resolve);
                xhr.addEventListener("error", reject);
            });
        } catch (error) {
            console.error("Upload error:", error);
            toastr.error("Upload failed due to an error");
            delete uploadProgressMap.value[file.name];
        }
    }

    uploading.value = false;
    uploadProgress.value = 0;
    currentUploadingFile.value = null;
    uploadProgressMap.value = {};
    event.target.value = "";
};

const updateVideo = async (video) => {
    try {
        const response = await fetch(
            route("video-editor.videos.update", video.id),
            {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    start_time: video.start_time,
                    end_time: video.end_time,
                }),
            }
        );

        if (!response.ok) {
            toastr.error("Update failed");
        } else {
            toastr.success("Video settings updated successfully");
        }
    } catch (error) {
        console.error("Update error:", error);
        toastr.error("Update failed due to an error");
    }
};

const deleteVideo = async (video) => {
    const result = await Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!",
    });

    if (!result.isConfirmed) return;

    // Debug: Check if CSRF token is available
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    console.log("CSRF Token:", csrfToken);

    try {
        const response = await fetch(
            route("video-editor.videos.destroy", video.id),
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            }
        );

        console.log("Response status:", response.status);
        console.log("Response headers:", response.headers);

        if (response.ok) {
            const index = props.project.videos.findIndex(
                (v) => v.id === video.id
            );
            if (index > -1) {
                props.project.videos.splice(index, 1);
            }
            toastr.success("Video deleted successfully");
        } else {
            const errorData = await response.json();
            toastr.error(
                "Failed to delete video: " +
                (errorData.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Delete error:", error);
        toastr.error("Delete failed due to an error");
    }
};

const exportVideo = async () => {
    exporting.value = true;
    exportProgress.value = 0;

    try {
        const response = await fetch(
            route("video-editor.export", props.project.id),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    text_overlay: textOverlay.value,
                }),
            }
        );

        const result = await response.json();

        if (result.success) {
            // Start polling for progress
            const pollProgress = async () => {
                try {
                    const statusResponse = await fetch(
                        route("video-editor.status", props.project.id)
                    );
                    const statusData = await statusResponse.json();

                    if (statusData.progress !== undefined) {
                        exportProgress.value = statusData.progress;
                    }

                    if (statusData.status === "processing") {
                        setTimeout(pollProgress, 1000); // Poll every second
                    } else if (statusData.status === "completed") {
                        exportProgress.value = 100;
                        toastr.success("Video exported successfully!");
                        props.project.status = "completed";
                        props.project.output_path = result.download_url;
                    } else if (statusData.status === "failed") {
                        toastr.error(
                            "Export failed: " +
                            (statusData.message || "Unknown error")
                        );
                    }
                } catch (error) {
                    console.error("Progress polling error:", error);
                    toastr.error("Error checking export progress");
                }
            };

            pollProgress();
        } else {
            toastr.error("Export failed: " + result.message);
        }
    } catch (error) {
        console.error("Export error:", error);
        toastr.error("Export failed due to an error");
    } finally {
        exporting.value = false;
        exportProgress.value = 0;
    }
};

const downloadVideo = () => {
    window.open(route("video-editor.download", props.project.id), "_blank");
};

const formatDuration = (seconds) => {
    if (!seconds) return "0:00";
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, "0")}`;
};

const formatFileSize = (bytes) => {
    if (!bytes) return "0 B";
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + " " + sizes[i];
};

const onVideoLoaded = (event, video) => {
    // Video metadata is already loaded from the server
};

const updateProject = () => {
    form.patch(route("video-editor.update", props.project.id), {
        onSuccess: () => {
            // Optionally show a success message
        },
    });
};

// Computed function to calculate trimmed duration dynamically
const getTrimmedDuration = (video) => {
    if (video.end_time && video.start_time) {
        return video.end_time - video.start_time;
    } else if (video.end_time) {
        return video.end_time - (video.start_time || 0);
    } else if (video.start_time) {
        return (video.duration || 0) - video.start_time;
    }
    return video.duration || 0;
};

// Computed function to calculate overall upload progress
const getOverallUploadProgress = () => {
    const progressValues = Object.values(uploadProgressMap.value);
    if (progressValues.length === 0) return 0;
    return Math.round(
        progressValues.reduce((sum, progress) => sum + progress, 0) /
        progressValues.length
    );
};

const onStartTimeChange = (video) => {
    if (
        video.start_time === "" ||
        video.start_time === null ||
        isNaN(video.start_time)
    ) {
        video.start_time = 0;
    }
    updateVideo(video);
};
</script>
