<template>
    <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <h1 class="text-3xl font-bold text-green-800">Edit Profile</h1>
            <p class="text-green-600">Update profile information</p>
          </div>
          <button
            @click="cancelEdit"
            class="flex items-center px-4 py-2 text-green-800 hover:text-green-600"
          >
            <i class="mr-2 pi pi-arrow-left"></i> Back
          </button>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="flex items-center justify-center h-64">
          <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="p-6 mb-6 bg-white shadow-md rounded-xl">
          <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
            <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
            <div>
              <h3 class="font-medium">Error</h3>
              <p>{{ error }}</p>
            </div>
          </div>
        </div>

        <!-- Edit form -->
        <div v-else class="overflow-hidden bg-white shadow-md rounded-xl">
          <form @submit.prevent="updateProfile" class="p-6">
            <!-- Success message -->
            <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
              <div class="flex items-center">
                <i class="mr-3 text-2xl pi pi-check-circle"></i>
                <div>
                  <h3 class="font-medium">Success!</h3>
                  <p>Profile updated successfully</p>
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
                >
              </div>
            </div>

            <!-- Form actions -->
            <div class="flex justify-end mt-6 space-x-3">
              <button
                type="button"
                @click="cancelEdit"
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
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import Parse from 'parse/dist/parse.min.js';

  const route = useRoute();
  const router = useRouter();
  const profileId = route.params.id;

  const loading = ref(true);
  const saving = ref(false);
  const error = ref(null);
  const success = ref(false);

  const profile = ref({
    id: '',
    username: '',
    email: '',
    phone: '',
    parseObject: null
  });

  // Fetch profile details
  const fetchProfile = async () => {
    loading.value = true;
    error.value = null;
    try {
      const Profile = Parse.Object.extend('Profile');
      const query = new Parse.Query(Profile);
      const result = await query.get(profileId);

      profile.value = {
        id: result.id,
        username: result.get('username'),
        email: result.get('email'),
        phone: result.get('phone') || '',
        parseObject: result
      };
    } catch (err) {
      console.error('Error fetching profile:', err);
      error.value = 'Failed to load profile. It may have been deleted.';
    } finally {
      loading.value = false;
    }
  };

  // Update profile
  const updateProfile = async () => {
    saving.value = true;
    error.value = null;
    success.value = false;

    try {
      const profileObj = profile.value.parseObject;

      // Update fields
      profileObj.set('username', profile.value.username);
      profileObj.set('email', profile.value.email);
      profileObj.set('phone', profile.value.phone || null);

      // Save the updated object
      await profileObj.save();

      success.value = true;
      setTimeout(() => {
        router.push(`/profiles/${profileId}`);
      }, 1500);
    } catch (err) {
      console.error('Error updating profile:', err);
      error.value = 'Failed to update profile. Please try again.';
    } finally {
      saving.value = false;
    }
  };

  // Cancel edit
  const cancelEdit = () => {
    router.push(`/profiles/${profileId}`);
  };

  // Load data on component mount
  onMounted(() => {
    fetchProfile();
  });
  </script>
