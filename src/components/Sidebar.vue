<!-- eslint-disable vue/multi-word-component-names -->

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';
import logo from '@/assets/logo.png'

// Sidebar State
const isOpen = ref(false);
const router = useRouter();
const showLogoutModal = ref(false);

// Toggle Sidebar
const toggleSidebar = () => {
  isOpen.value = !isOpen.value;
};

// Close Sidebar When Clicking a Link (Only in Mobile View)
const handleNavClick = () => {
  if (window.innerWidth < 800) {
    isOpen.value = false;
  }
};

// Active Link Styling
const route = useRoute();
const isActiveLink = (path) => route.path === path;

// Logout Confirmation
const handleLogout = () => {
  showLogoutModal.value = true;
};

const confirmLogout = async () => {
  showLogoutModal.value = false;
  try {
    // Remove session token from localStorage
    localStorage.removeItem('sessionToken');

    // Log out the current user
    await Parse.User.logOut();
    console.log('User logged out successfully');

    // Redirect to login page
    router.push('/');
  } catch (error) {
    console.error('Logout error:', error);
    // Still redirect even if there's an error with Parse
    router.push('/');
  }
};

const cancelLogout = () => {
  showLogoutModal.value = false;
};
</script>

<template>
  <div>
    <!-- Sidebar Toggle Button (Hidden when Sidebar is Open) -->
    <button
      v-if="!isOpen"
      @click="toggleSidebar"
      class="fixed z-10 px-3 py-2 text-white bg-green-700 rounded-md top-5 left-5 lg:hidden"
    >
      <i class="pi pi-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside
        :class="[
          isOpen ? 'translate-x-0 ' : '-translate-x-full',
          'fixed lg:translate-x-0 top-0 left-0 w-64 md:w-[20%] bg-green-900 px-6 pt-6 h-screen overflow-y-auto scroll-smooth transition-transform duration-300 z-50'
        ]"
      >

      <!-- Close Button (X Icon) -->
      <button
        @click="toggleSidebar"
        class="absolute p-2 text-white bg-red-500 rounded-md top-3 right-3 lg:hidden"
      >
        <i class="pi pi-times"></i>
      </button>

      <!-- Sidebar Header -->
      <div class="items-center px-2 py-1 pb-10 rounded-full ">
        <img :src="logo" alt="" class="">
        <!-- <h1 class="font-serif text-3xl font-bold text-green-400 ">Mind<span class="text-white">Lab</span></h1> -->
      </div>

      <!-- Navigation -->
      <nav class="text-center">
        <RouterLink to="/dashboard" @click="handleNavClick">
          <div :class="[isActiveLink('/dashboard') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-white pi pi-ethereum"></i>
            <h3 class="text-gray-200">Health List</h3>
          </div>
        </RouterLink>
        <RouterLink to="/meals" @click="handleNavClick">
          <div :class="[isActiveLink('/meals') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-orange-400 pi pi-utensils"></i>
            <h3 class="text-gray-200">Meals</h3>
          </div>
        </RouterLink>

        <RouterLink to="/meal-plan" @click="handleNavClick">
          <div :class="[isActiveLink('/meal-plan') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-red-400 pi pi-slack"></i>
            <h3 class="text-gray-200">Meal Plans</h3>
          </div>
        </RouterLink>

        <RouterLink to="/subscriptions" @click="handleNavClick">
          <div :class="[isActiveLink('/subscriptions') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-yellow-500 pi pi-crown"></i>
            <h3 class="text-gray-200">Subscriptions</h3>
          </div>
        </RouterLink>

        <RouterLink to="/community" @click="handleNavClick">
          <div :class="[isActiveLink('/community') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-white pi pi-user"></i>
            <h3 class="text-gray-200">Community</h3>
          </div>
        </RouterLink>

        <!-- New Navigation Items -->
        <RouterLink to="/health-record" @click="handleNavClick">
          <div :class="[isActiveLink('/health-record') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-blue-400 pi pi-heart"></i>
            <h3 class="text-gray-200">Health Record</h3>
          </div>
        </RouterLink>

        <RouterLink to="/ingredients" @click="handleNavClick">
          <div :class="[isActiveLink('/ingredients') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-green-400 pi pi-apple"></i>
            <h3 class="text-gray-200">Ingredients</h3>
          </div>
        </RouterLink>

        <RouterLink to="/notes" @click="handleNavClick">
          <div :class="[isActiveLink('/notes') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-purple-400 pi pi-pencil"></i>
            <h3 class="text-gray-200">Notes</h3>
          </div>
        </RouterLink>

        <RouterLink to="/profiles" @click="handleNavClick">
          <div :class="[isActiveLink('/profiles') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-teal-400 pi pi-id-card"></i>
            <h3 class="text-gray-200">Profiles</h3>
          </div>
        </RouterLink>

        <RouterLink to="/menu-item" @click="handleNavClick">
          <div :class="[isActiveLink('/menu-item') ? 'bg-green-600 hover:bg-green-500' : '', 'flex space-x-2 py-2 px-2 my-4 border rounded-md shadow-md hover:scale-110 transition-transform']">
            <i class="pt-1 text-pink-400 pi pi-list"></i>
            <h3 class="text-gray-200">Menu Item</h3>
          </div>
        </RouterLink>

        <button @click="handleLogout" class="w-full text-left">
          <div class="flex px-2 py-2 my-4 space-x-2 transition-transform border rounded-md shadow-md hover:bg-red-500">
            <i class="pt-1 text-white pi pi-sign-out"></i>
            <h3 class="text-gray-200">Logout</h3>
          </div>
        </button>
      </nav>
    </aside>

    <!-- Overlay (For Small Screens) -->
    <div
      v-if="isOpen"
      @click="toggleSidebar"
      class="fixed inset-0 bg-black bg-opacity-50 lg:hidden"
    ></div>

    <!-- Logout Confirmation Modal -->
    <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center px-10 bg-black bg-opacity-50">
      <div class="p-6 bg-white rounded-md shadow-md w-96">
        <h3 class="text-lg font-semibold text-gray-800">Confirm Logout</h3>
        <p class="mt-2 text-gray-600">Are you sure you want to leave?</p>
        <div class="flex justify-end mt-4 space-x-2">
          <button @click="cancelLogout" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-600 hover:text-white">Cancel</button>
          <button @click="confirmLogout" class="px-4 py-2 text-white bg-red-500 rounded-md hover:bg-red-700 hover:text-black">Leave</button>
        </div>
      </div>
    </div>
  </div>
</template>
<style>
/* Hide scrollbar for a cleaner look */
.sidebar::-webkit-scrollbar {
  width: 5px;
}
.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
}
</style>
