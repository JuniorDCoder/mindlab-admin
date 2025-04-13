<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="p-8 bg-white shadow-md rounded-xl">
        <div class="flex items-center justify-center">
          <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
            <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
            <div>
              <h3 class="font-medium">Error</h3>
              <p>{{ error }}</p>
            </div>
          </div>
        </div>
        <div class="mt-6 text-center">
          <button
            @click="() => router.push('/profiles')"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            Back to Profiles
          </button>
        </div>
      </div>

      <!-- Profile details -->
      <div v-else class="overflow-hidden bg-white shadow-md rounded-xl">
        <!-- Header -->
        <div class="relative">
          <div class="h-48 bg-gradient-to-r from-green-700 to-green-500">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
          </div>
          <div class="absolute top-0 left-0 flex items-center justify-between w-full h-full px-6">
            <h1 class="text-3xl font-bold text-white">{{ profile.username }}</h1>
            <div class="flex space-x-3">
              <router-link
                :to="`/profiles/${profile.id}/edit`"
                class="flex items-center px-4 py-2 text-white bg-yellow-600 rounded-lg hover:bg-yellow-700"
              >
                <i class="mr-2 pi pi-pencil"></i>
                Edit
              </router-link>
              <button
                @click="confirmDelete"
                class="flex items-center px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
              >
                <i class="mr-2 pi pi-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="flex flex-col md:flex-row">
            <!-- Avatar -->
            <div class="w-full mb-6 md:w-1/3 md:mb-0 md:pr-6">
              <div class="flex items-center justify-center w-32 h-32 mx-auto text-5xl text-white bg-green-600 rounded-full">
                {{ profile.username.charAt(0).toUpperCase() }}
              </div>
            </div>

            <!-- Details -->
            <div class="w-full md:w-2/3">
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Profile Information</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <p class="text-sm font-medium text-gray-500">Username</p>
                    <p class="text-gray-800">{{ profile.username }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Email</p>
                    <p class="text-gray-800">{{ profile.email }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Phone</p>
                    <p class="text-gray-800">{{ profile.phone || 'Not provided' }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Created</p>
                    <p class="text-gray-800">{{ formatDate(profile.createdAt) }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Last Updated</p>
                    <p class="text-gray-800">{{ formatDate(profile.updatedAt) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete confirmation modal -->
      <DeleteConfirmationModal
        v-if="showDeleteModal"
        :item-name="profile.username"
        @confirm="deleteProfile"
        @cancel="showDeleteModal = false"
        :loading="deleting"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';
import DeleteConfirmationModal from '@/components/DeleteConfirmationModal.vue';

const route = useRoute();
const router = useRouter();
const profileId = route.params.id;

const loading = ref(true);
const error = ref(null);
const profile = ref({
  id: '',
  username: '',
  email: '',
  phone: '',
  createdAt: '',
  updatedAt: '',
  parseObject: null
});
const showDeleteModal = ref(false);
const deleting = ref(false);

// Format date for display
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Fetch profile details
const fetchProfile = async () => {
  loading.value = true;
  error.value = null;
  try {
    const Profile = Parse.Object.extend('Profile');
    const query = new Parse.Query(Profile);
    query.include('user');
    const result = await query.get(profileId);

    profile.value = {
      id: result.id,
      username: result.get('username'),
      email: result.get('email'),
      phone: result.get('phone'),
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
      user: result.get('user'),
      parseObject: result
    };
  } catch (err) {
    console.error('Error fetching profile:', err);
    error.value = 'Failed to load profile. It may have been deleted.';
  } finally {
    loading.value = false;
  }
};

// Confirm delete action
const confirmDelete = () => {
  showDeleteModal.value = true;
};

// Delete profile
const deleteProfile = async () => {
  deleting.value = true;
  try {
    await profile.value.parseObject.destroy();
    router.push('/profiles');
  } catch (err) {
    console.error('Error deleting profile:', err);
    error.value = 'Failed to delete profile. Please try again.';
    showDeleteModal.value = false;
  } finally {
    deleting.value = false;
  }
};

// Load data on component mount
onMounted(() => {
  fetchProfile();
});
</script>
