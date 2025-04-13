<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">Create New Profile</h1>
          <p class="text-green-600">Add a new user profile to the system</p>
        </div>
        <button
          @click="cancelCreate"
          class="flex items-center px-4 py-2 text-green-800 hover:text-green-600"
        >
          <i class="mr-2 pi pi-arrow-left"></i> Back
        </button>
      </div>

      <!-- Form -->
      <div class="overflow-hidden bg-white shadow-md rounded-xl">
        <form @submit.prevent="createProfile" class="p-6">
          <!-- Error message -->
          <div v-if="error" class="p-4 mb-6 text-red-700 bg-red-100 rounded-lg">
            <div class="flex items-center">
              <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
              <div>
                <h3 class="font-medium">Error</h3>
                <p>{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Success message -->
          <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
            <div class="flex items-center">
              <i class="mr-3 text-2xl pi pi-check-circle"></i>
              <div>
                <h3 class="font-medium">Success!</h3>
                <p>Profile created successfully. Redirecting...</p>
              </div>
            </div>
          </div>

          <!-- Form fields -->
          <div class="grid gap-6">
            <!-- Username -->
            <div>
              <label for="username" class="block mb-2 text-sm font-medium text-gray-700">
                Username <span class="text-red-500">*</span>
              </label>
              <input
                id="username"
                v-model="profile.username"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Enter username"
              >
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block mb-2 text-sm font-medium text-gray-700">
                Email <span class="text-red-500">*</span>
              </label>
              <input
                id="email"
                v-model="profile.email"
                type="email"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Enter email"
              >
            </div>

            <!-- Phone -->
            <div>
              <label for="phone" class="block mb-2 text-sm font-medium text-gray-700">
                Phone
              </label>
              <input
                id="phone"
                v-model="profile.phone"
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Enter phone number"
              >
            </div>

            <!-- User Selection -->
            <div>
              <label for="user" class="block mb-2 text-sm font-medium text-gray-700">
                Link to User Account <span class="text-red-500">*</span>
              </label>
              <select
                id="user"
                v-model="profile.userId"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              >
                <option value="">Select a user</option>
                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                  {{ user.username }} ({{ user.email }})
                </option>
              </select>
            </div>
          </div>

          <!-- Form actions -->
          <div class="flex justify-end mt-6 space-x-3">
            <button
              type="button"
              @click="cancelCreate"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
            >
              <span v-if="saving" class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></span>
              {{ saving ? 'Creating...' : 'Create Profile' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();

const profile = ref({
  username: '',
  email: '',
  phone: '',
  userId: ''
});

const availableUsers = ref([]);
const loadingUsers = ref(false);
const saving = ref(false);
const error = ref(null);
const success = ref(false);

// Fetch available users
const fetchAvailableUsers = async () => {
  loadingUsers.value = true;
  try {
    const results = await Parse.Cloud.run('getAllUsers');
    availableUsers.value = results;
  } catch (err) {
    console.error('Error fetching users:', err);
    error.value = 'Failed to load users.';
  } finally {
    loadingUsers.value = false;
  }
};

// Create new profile
const createProfile = async () => {
  saving.value = true;
  error.value = null;
  success.value = false;

  try {
    // Create new Parse object
    const Profile = Parse.Object.extend('Profile');
    const newProfile = new Profile();

    // Set basic fields
    newProfile.set('username', profile.value.username);
    newProfile.set('email', profile.value.email);
    newProfile.set('phone', profile.value.phone || null);

    // Set user pointer
    if (profile.value.userId) {
      const userPointer = Parse.User.createWithoutData(profile.value.userId);
      newProfile.set('user', userPointer);
    }

    // Set ACL (Access Control List)
    const acl = new Parse.ACL();
    acl.setPublicReadAccess(true);
    acl.setPublicWriteAccess(true);
    newProfile.setACL(acl);


    // Save to database
    const savedProfile = await newProfile.save();

    // Show success and redirect
    success.value = true;
    setTimeout(() => {
      router.push(`/profiles/${savedProfile.id}`);
    }, 1500);

  } catch (err) {
    console.error('Error creating profile:', err);
    error.value = err.message || 'Failed to create profile. Please try again.';
  } finally {
    saving.value = false;
  }
};

// Cancel creation
const cancelCreate = () => {
  router.push('/profiles');
};

// Load data on component mount
onMounted(() => {
  fetchAvailableUsers();
});
</script>

<style scoped>
/* Smooth transitions for form elements */
input, select {
  transition: all 0.3s ease;
}

/* Focus styles */
input:focus, select:focus {
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  outline: none;
}
</style>
