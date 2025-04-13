<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">My Profile</h1>
          <p class="text-green-600">Manage your account information</p>
        </div>
      </div>

      <div class="overflow-hidden bg-white shadow-md rounded-xl">
        <div v-if="loading" class="flex items-center justify-center py-16">
          <span class="w-8 h-8 border-t-4 border-green-500 rounded-full animate-spin"></span>
        </div>

        <form v-else @submit.prevent="saveChanges" class="p-6">
          <!-- Error -->
          <div v-if="error" class="p-4 mb-6 text-red-700 bg-red-100 rounded-lg">
            <div class="flex items-center">
              <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
              <div>
                <h3 class="font-medium">Error</h3>
                <p>{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Success -->
          <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
            <div class="flex items-center">
              <i class="mr-3 text-2xl pi pi-check-circle"></i>
              <div>
                <h3 class="font-medium">Profile Updated!</h3>
                <p>Your changes have been saved.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-6">
            <!-- Username -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Username</label>
              <input
                v-model="form.username"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>

            <!-- Email -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Email</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>

            <!-- Phone -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>

            <!-- Role -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Role</label>
              <input
                v-model="form.role"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>

            <!-- Email Verified -->
            <div class="flex items-center">
              <input type="checkbox" :checked="form.emailVerified" disabled class="mr-2" />
              <label class="text-sm text-gray-600">Email Verified</label>
            </div>

            <!-- Auth Date -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Auth Date</label>
              <input
                type="text"
                :value="formattedDate(form.authData)"
                disabled
                class="w-full px-4 py-2 text-sm bg-gray-100 border border-gray-200 rounded-lg"
              />
            </div>

            <!-- Created At -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Created At</label>
              <input
                type="text"
                :value="formatDate(form.createdAt)"
                disabled
                class="w-full px-4 py-2 text-sm bg-gray-100 border border-gray-200 rounded-lg"
              />
            </div>

            <!-- Updated At -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Last Updated</label>
              <input
                type="text"
                :value="formatDate(form.updatedAt)"
                disabled
                class="w-full px-4 py-2 text-sm bg-gray-100 border border-gray-200 rounded-lg"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end mt-6 space-x-3">
            <button
              type="submit"
              :disabled="saving"
              class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
            >
              <span
                v-if="saving"
                class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"
              ></span>
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
import Parse from 'parse/dist/parse.min.js';

const form = ref({
  username: '',
  email: '',
  phone: '',
  role: '',
  emailVerified: false,
  authData: null,
  createdAt: '',
  updatedAt: ''
});

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(false);

const loadProfile = async () => {
  loading.value = true;
  try {
    const currentUser = await Parse.User.currentAsync();
    if (!currentUser) {
      throw new Error('User not logged in');
    }

    // Populate form with user data
    form.value = {
      username: currentUser.get('username') || '',
      email: currentUser.get('email') || '',
      phone: currentUser.get('phone') || '',
      role: currentUser.get('role') || '',
      emailVerified: currentUser.get('emailVerified') || false,
      authData: currentUser.get('authData'),
      createdAt: currentUser.createdAt,
      updatedAt: currentUser.updatedAt
    };
  } catch (err) {
    console.error('Failed to load profile:', err);
    error.value = err.message || 'Could not load user profile.';
  } finally {
    loading.value = false;
  }
};

const saveChanges = async () => {
  saving.value = true;
  error.value = null;
  success.value = false;

  try {
    const currentUser = await Parse.User.currentAsync();
    currentUser.set('username', form.value.username);
    currentUser.set('email', form.value.email);
    currentUser.set('phone', form.value.phone);
    currentUser.set('role', form.value.role);

    await currentUser.save();
    success.value = true;
    form.value.updatedAt = currentUser.updatedAt;
  } catch (err) {
    console.error('Save error:', err);
    error.value = err.message || 'Failed to save profile.';
  } finally {
    saving.value = false;
  }
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleString();
};

const formattedDate = (authData) => {
  if (!authData) return 'N/A';
  const provider = Object.keys(authData)[0];
  return `Via ${provider}`;
};

onMounted(() => {
  loadProfile();
});
</script>

<style scoped>
input, select {
  transition: all 0.3s ease;
}

input:focus, select:focus {
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  outline: none;
}
</style>
