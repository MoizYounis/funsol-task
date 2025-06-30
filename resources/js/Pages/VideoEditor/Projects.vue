<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Video Projects
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium">
                                Your Video Projects
                            </h3>
                            <Link
                                :href="route('video-editor.create')"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Create New Project
                            </Link>
                        </div>

                        <div
                            v-if="projects.length === 0"
                            class="text-center py-8"
                        >
                            <p class="text-gray-500 mb-4">
                                No video projects yet.
                            </p>
                            <Link
                                :href="route('video-editor.create')"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Create Your First Project
                            </Link>
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <div
                                v-for="project in projects"
                                :key="project.id"
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                            >
                                <div
                                    class="flex justify-between items-start mb-3"
                                >
                                    <h4 class="font-semibold text-lg">
                                        {{ project.name }}
                                    </h4>
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                project.status === 'completed',
                                            'bg-yellow-100 text-yellow-800':
                                                project.status === 'processing',
                                            'bg-red-100 text-red-800':
                                                project.status === 'failed',
                                            'bg-gray-100 text-gray-800':
                                                project.status === 'draft',
                                        }"
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ project.status }}
                                    </span>
                                </div>

                                <p
                                    v-if="project.description"
                                    class="text-gray-600 text-sm mb-3"
                                >
                                    {{ project.description }}
                                </p>

                                <div class="text-sm text-gray-500 mb-3">
                                    <p>Videos: {{ project.videos_count }}/4</p>
                                    <p>
                                        Created:
                                        {{ formatDate(project.created_at) }}
                                    </p>
                                </div>

                                <div class="flex space-x-2">
                                    <Link
                                        :href="
                                            route(
                                                'video-editor.edit',
                                                project.id
                                            )
                                        "
                                        class="bg-blue-500 hover:bg-blue-700 text-white text-sm py-1 px-3 rounded"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        v-if="project.status === 'completed'"
                                        @click="downloadProject(project)"
                                        class="bg-green-500 hover:bg-green-700 text-white text-sm py-1 px-3 rounded"
                                    >
                                        Download
                                    </button>

                                    <button
                                        @click="deleteProject(project)"
                                        class="bg-red-500 hover:bg-red-700 text-white text-sm py-1 px-3 rounded"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    projects: Array,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString();
};

const downloadProject = (project) => {
    window.open(route("video-editor.download", project.id), "_blank");
};

const deleteProject = (project) => {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("video-editor.destroy", project.id));
        }
    });
};
</script>
