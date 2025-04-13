<!-- eslint-disable vue/multi-word-component-names -->

<script setup>
import R from '@/assets/R.png';
import { ref, onMounted } from 'vue';
import { Bar, Pie } from "vue-chartjs";
import { Chart as ChartJS, BarElement, ArcElement, CategoryScale, LinearScale, Tooltip } from "chart.js";
import Parse from 'parse/dist/parse.min.js';
import { useRouter } from 'vue-router';

ChartJS.register(BarElement, ArcElement, CategoryScale, LinearScale, Tooltip);

const router = useRouter();
const currentUser = ref(null);
const showDropdown = ref(false);
const isLoggingOut = ref(false);
const isLoading = ref(true);

const modelCounts = ref({});
const barChartData = ref(null);
const pieChartData = ref(null);

const CACHE_EXPIRY = 5 * 60 * 1000; // 5 minutes

// Helpers for cache
function getCache(key, expiry) {
  const cached = localStorage.getItem(key);
  if (!cached) return null;

  try {
    const { data, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp < expiry) {
      return data;
    }
  } catch (e) {
    console.warn("Cache parse error:", e);
  }

  return null;
}

function setCache(key, data) {
  localStorage.setItem(key, JSON.stringify({ data, timestamp: Date.now() }));
}

// Update chart data
function updateCharts(data) {
  const validCounts = Object.entries(data).filter(([_, count]) => typeof count === 'number');

  barChartData.value = {
    labels: validCounts.map(([model]) => model),
    datasets: [
      {
        label: "Model Counts",
        backgroundColor: "#047857",
        data: validCounts.map(([_, count]) => count),
      },
    ],
  };

  pieChartData.value = {
    labels: validCounts.map(([model]) => model),
    datasets: [
      {
        backgroundColor: ["#10B981", "#34D399", "#6EE7B7", "#A7F3D0", "#D1FAE5", "#6EE7B7", "#34D399", "#10B981"],
        data: validCounts.map(([_, count]) => count),
      },
    ],
  };
}

// Main fetch logic
const fetchModelCounts = async () => {
  const userId = Parse.User.current()?.id || 'guest';
  const CACHE_KEY = `modelCountsCache_${userId}`;

  const cached = getCache(CACHE_KEY, CACHE_EXPIRY);
  if (cached) {
    console.log('Using cached data');
    modelCounts.value = cached;
    updateCharts(cached);
    isLoading.value = false;
    return;
  }

  console.log('Fetching fresh data');
  const models = [
    '_Role', '_Session', 'User', 'B4aCustomField', 'B4aMenuItem', 'B4aSetting', 'B4aVehicle',
    'HealthRecord', 'Ingredients', 'MealPlans', 'Meals', 'Notes', 'Profile'
  ];

  const counts = {};
  for (const model of models) {
    try {
      const query = new Parse.Query(model);
      counts[model] = await query.count();
    } catch (error) {
      console.warn(`Error fetching count for model ${model}:`, error.message);
      counts[model] = 'Permission Denied';
    }
  }

  modelCounts.value = counts;
  updateCharts(counts);
  setCache(CACHE_KEY, counts);
  isLoading.value = false;
};

// Load user + fetch data on mount
onMounted(async () => {
  try {
    const user = Parse.User.current();
    if (user) {
      currentUser.value = {
        username: user.get('username') || 'User',
        fullName: user.get('fullName') || user.get('username') || 'User',
        role: user.get('role') || 'Admin'
      };
    }

    await fetchModelCounts();
  } catch (error) {
    console.error('Error during mounted logic:', error);
  }
});

// UI interaction methods
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};

const closeDropdown = (e) => {
  if (!e.target.closest('.user-menu')) {
    showDropdown.value = false;
  }
};

const handleLogout = async () => {
  try {
    isLoggingOut.value = true;
    localStorage.removeItem('sessionToken');
    await Parse.User.logOut();
    router.push('/');
  } catch (error) {
    console.error('Logout error:', error);
    router.push('/');
  } finally {
    isLoggingOut.value = false;
  }
};

const goToProfile = () => {
  showDropdown.value = false;
  router.push('/profile');
};
</script>

<template>
  <div @click="closeDropdown" class="min-h-screen">
    <div class="fixed top-0 left-0 z-10 flex items-center justify-between w-full p-5 bg-green-900 px-4 sm:px-6 md:px-10 lg:pl-[20%] rounded-b-md">

      <h1 class="hidden text-xl font-semibold text-green-50 lg:flex">Dashboard</h1>

      <!-- User Profile Section with Dropdown -->
      <div class="relative ml-auto user-menu">
        <div @click="toggleDropdown" class="flex items-center space-x-3 cursor-pointer">
          <img :src="R" alt="" class="w-auto h-10 bg-white rounded-full">
          <span class="text-xs text-white text-start">
            <h1>{{ currentUser?.fullName || 'Loading...' }}</h1>
            <p>{{ currentUser?.role || 'User' }}</p>
          </span>
          <i class="text-white pi pi-chevron-down"></i>
        </div>

        <!-- Dropdown Menu -->
        <div v-if="showDropdown" class="absolute right-0 z-50 w-48 py-1 mt-2 bg-white rounded-md shadow-lg">
          <button @click="goToProfile" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-green-100">
            <i class="mr-2 pi pi-user"></i> Profile
          </button>
          <button
            @click="handleLogout"
            class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-green-100"
            :disabled="isLoggingOut"
          >
            <i class="mr-2 pi pi-sign-out"></i> {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
          </button>
        </div>
      </div>
    </div>

    <div class="flex h-full p-5 mt-16 bg-green-50 lg:ml-[20%]">
      <!-- Main Content -->
      <main class="flex-1 p-6">
        <h1 class="text-xl font-bold text-green-900 md:text-2xl">
          Welcome to your Dashboard <span class="text-gray-600">{{ currentUser?.username || '' }}</span>
        </h1>

        <!-- Loading Spinner -->
        <div v-if="isLoading" class="flex items-center justify-center h-64">
          <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
        </div>

        <!-- Stats Cards -->
        <div v-else class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-3">
          <div
            v-for="(count, model) in modelCounts"
            :key="model"
            class="p-4 bg-white rounded-lg shadow"
          >
            <h2 class="text-gray-700">{{ model }}</h2>
            <p v-if="typeof count === 'number'" class="text-2xl font-bold text-green-700">{{ count }}</p>
            <p v-else class="text-sm text-red-500">Permission Denied</p>
          </div>
        </div>

        <!-- Charts -->
        <div v-if="!isLoading" class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2">
          <div v-if="barChartData" class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-gray-700">Model Counts</h2>
            <Bar :data="barChartData" :options="{ responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }" />
          </div>
          <div v-if="pieChartData" class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-gray-700">Model Distribution</h2>
            <Pie :data="pieChartData" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.user-menu {
  position: relative;
  display: inline-block;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #047857;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
