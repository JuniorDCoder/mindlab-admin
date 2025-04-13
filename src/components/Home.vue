<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import Parse from 'parse/dist/parse.min.js';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({
  username: '',
  password: '',
  rememberMe: false
});
const showPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  // Validate inputs first
  if (!form.value.username || !form.value.password) {
    errorMessage.value = "Username and password are required";
    loading.value = false;
    return;
  }

  try {
    // Ensure username and password are strings
    const username = String(form.value.username);
    const password = String(form.value.password);

    const user = await Parse.User.logIn(username, password);

    if (user.get('isAdmin') !== true) {
      console.log("Login successful:", user);

    // Store session token if remember me is checked
    if (form.value.rememberMe) {
      localStorage.setItem('sessionToken', user.getSessionToken());
    }

    // Redirect to the dashboard
    router.push('/dashboard');
    } else {
      errorMessage.value = "You do not have permission to access this application.";
      console.log("User is not an admin:", user);
      form.value.password = '';
    }

  } catch (error) {
    console.error("Login failed:", error);
    errorMessage.value = error.message || "Login failed. Please try again.";

    form.value.password = ''
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="flex items-center justify-center min-h-screen px-4 py-10 bg-green-100">
    <div class="flex flex-col w-full max-w-4xl overflow-hidden bg-white rounded-lg shadow-lg md:flex-row">
      <div class="flex-col items-center justify-center hidden p-8 bg-gray-900 md:flex md:w-1/2">
        <img src="@/assets/logo.png" alt="Logo">
        <img src="@/assets/heu.png" alt="Illustration" class="w-full max-w-sm">
      </div>
      <div class="w-full p-8 md:w-1/2">
        <img src="@/assets/logo.png" alt="Logo" class="flex px-20 md:px-44 md:hidden">
        <h2 class="text-2xl font-semibold text-center text-gray-700">Admin Login</h2>
        <form @submit.prevent="handleLogin" class="mt-6">
          <div>
            <label class="block text-gray-700">Username</label>
            <input type="text" v-model="form.username" required class="w-full p-2 mt-2 border rounded-md">
          </div>
          <p v-if="errorMessage" class="mt-2 text-sm text-red-500">{{ errorMessage }}</p>
          <div class="relative mt-4">
            <label class="block text-gray-700">Password</label>
            <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required class="w-full p-2 mt-2 border rounded-md">
            <span @click="showPassword = !showPassword" class="absolute cursor-pointer right-3 top-10">
              {{ showPassword ? '🙈' : '👁' }}
            </span>
          </div>
          <div class="flex items-center justify-between mt-2">
            <label class="flex items-center">
              <input type="checkbox" v-model="form.rememberMe" class="mr-2">
              Remember me
            </label>
            <a href="#" class="text-sm text-blue-500 hover:underline">Forgot Password?</a>
          </div>

          <button type="submit" class="w-full py-2 mt-4 text-white bg-green-500 rounded-md hover:bg-green-600" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
