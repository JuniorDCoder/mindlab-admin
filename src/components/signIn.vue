<script setup>
import { ref } from 'vue';
import heu from '@/assets/heu.png';
import { RouterLink } from 'vue-router';
import logo from '@/assets/logo.png';

const email = ref('');
const phone = ref('');
const address = ref('');
const role = ref('admin');
const password = ref('');
const showPassword = ref(false);

const handleSignup = () => {
  console.log("Email:", email.value);
  console.log("Phone:", phone.value);
  console.log("Address:", address.value);
  console.log("Role:", role.value);
  console.log("Password:", password.value);

};
</script>

<style>
@media (max-width: 768px) {
  .bg-gray-800 {
    display: none;
  }
}
</style>


<template>
  <div class="flex min-h-screen items-center justify-center bg-green-100 px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden w-full max-w-4xl flex flex-col md:flex-row">
      <!-- Left Side - Illustration -->
      <div class="hidden md:flex md:w-1/2 bg-gray-800 px-8 py-3 flex-col justify-center items-center">
        <img :src="logo" alt="">
        <img :src="heu" alt="Illustration" class="w-full max-w-sm">
      </div>

      <!-- Right Side - Sign Up Form -->
      <div class="w-full md:w-1/2 p-8 justify-center items-center">
        <img :src="logo" alt="" class="md:px-44 md:hidden flex px-20">
        <h2 class="text-2xl font-semibold text-gray-700 text-center">Sign Up</h2>

        <form @submit.prevent="handleSignup" class="mt-6">
          <div>
            <label class="block text-gray-700">Email</label>
            <input type="email" v-model="email" required class="w-full mt-2 p-2 border rounded-md">
          </div>

          <div class="mt-4">
            <label class="block text-gray-700">Phone Number</label>
            <input type="tel" v-model="phone" required class="w-full mt-2 p-2 border rounded-md">
          </div>

          <div class="mt-4">
            <label class="block text-gray-700">Address</label>
            <input type="text" v-model="address" required class="w-full mt-2 p-2 border rounded-md">
          </div>

          <div class="mt-4">
            <label class="block text-gray-700">Role</label>
            <select v-model="role" required class="w-full mt-2 p-2 border rounded-md">
              <option value="admin">Admin</option>
              <option value="guest">Guest</option>
            </select>
          </div>

          <div class="mt-4 relative">
            <label class="block text-gray-700">Password</label>
            <input :type="showPassword ? 'text' : 'password'" v-model="password" required class="w-full mt-2 p-2 border rounded-md">
            <span @click="showPassword = !showPassword" class="absolute right-3 top-10 cursor-pointer">
              {{ showPassword ? '🙈' : '👁' }}
            </span>
          </div>

          <button type="submit" class="w-full bg-green-500 text-white py-2 mt-4 rounded-md hover:bg-green-600">Sign Up</button>
        </form>

        <p class="mt-4 text-center">Already have an account? <RouterLink to="/" class="text-blue-500 hover:underline">Log in</RouterLink></p>
      </div>
    </div>
  </div>
</template>

