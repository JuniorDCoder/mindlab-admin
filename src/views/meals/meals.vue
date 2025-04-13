<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref, onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

// Refs for data
const meals = ref([]);
const cart = ref([]);

const searchQuery = ref('');
const isLoading = ref(true);
const error = ref(null);

// Delete confirmation modal
const showDeleteModal = ref(false);
const mealToDelete = ref(null);

// Cache management - set cache expiry to 5 minutes
const CACHE_EXPIRY_TIME = 5 * 60 * 1000; // 5 minutes in milliseconds
const MEALS_CACHE_KEY = 'mealsData';

// Check if the cache is valid
const isCacheValid = () => {
  const cachedData = localStorage.getItem(MEALS_CACHE_KEY);
  if (!cachedData) return false;

  const { timestamp } = JSON.parse(cachedData);
  return Date.now() - timestamp < CACHE_EXPIRY_TIME;
};

// Fetch meals from Parse
const fetchMeals = async () => {
  isLoading.value = true;
  error.value = null;

  try {
    // Check if we have valid cached data
    if (isCacheValid()) {
      const { data } = JSON.parse(localStorage.getItem(MEALS_CACHE_KEY));
      meals.value = data;
      isLoading.value = false;
      console.log('Using cached meals data');
      return;
    }

    // Create Parse query for Meals
    const Meal = Parse.Object.extend('Meals');
    const query = new Parse.Query(Meal);

    // Include related objects if needed
    query.include('ingredients');

    // Fetch meals
    const results = await query.find();

    // Transform Parse objects to plain objects
    const mealsData = results.map(meal => ({
      id: meal.id,
      name: meal.get('name'),
      quantity: 1,
      image: meal.get('image') ? meal.get('image').url() : new URL("@/assets/default-meal.png", import.meta.url).href,
      description: meal.get('description') || 'No description available',
      mealType: meal.get('mealType'),
      ingredients: meal.get('ingredients') || [],
      schedule: meal.get('schedule'),
      note: meal.get('note'),
      parseObject: meal // Store reference to original Parse object
    }));

    // Update state and cache
    meals.value = mealsData;

    // Cache the data with timestamp
    localStorage.setItem(MEALS_CACHE_KEY, JSON.stringify({
      timestamp: Date.now(),
      data: mealsData
    }));

  } catch (error) {
    console.error('Error fetching meals:', error);
    error.value = 'Failed to load meals. Please try again later.';
  } finally {
    isLoading.value = false;
  }
};

// Prompt for meal deletion
const confirmDeleteMeal = (meal) => {
  mealToDelete.value = meal;
  showDeleteModal.value = true;
};

// Delete a meal from Parse
const deleteMeal = async () => {
  if (!mealToDelete.value) return;

  try {
    const meal = mealToDelete.value;

    if (!meal.parseObject) {
      throw new Error('Meal not found');
    }

    await meal.parseObject.destroy();

    // Update local state
    meals.value = meals.value.filter(m => m.id !== meal.id);

    // Update cache
    localStorage.setItem(MEALS_CACHE_KEY, JSON.stringify({
      timestamp: Date.now(),
      data: meals.value
    }));

    // Show success notification (you could implement a toast system here)
    // For now, we'll just close the modal
    showDeleteModal.value = false;
    mealToDelete.value = null;
  } catch (error) {
    console.error('Error deleting meal:', error);
    alert('Failed to delete meal: ' + error.message);
  }
};

// Cancel deletion and close modal
const cancelDelete = () => {
  showDeleteModal.value = false;
  mealToDelete.value = null;
};

// Computed property for filtered meals
const filteredMeals = computed(() => {
  if (!searchQuery.value) return meals.value;
  return meals.value.filter(meal =>
    meal.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// Load cart from LocalStorage
onMounted(() => {
  fetchMeals();

  const storedCart = JSON.parse(localStorage.getItem('mealCart')) || [];
  cart.value = storedCart;
});

// Force refresh meals data
const refreshMeals = () => {
  localStorage.removeItem(MEALS_CACHE_KEY);
  fetchMeals();
};
</script>

<template>
  <div class="h-screen bg-green-100">
    <!-- Header -->
    <div class="fixed z-20 top-0 left-0 flex items-center justify-between w-full p-5 bg-green-900 lg:space-x-20 lg:px-20 lg:pl-[20%] lg:ml-10 rounded-b-md">
      <h1 class="hidden text-xl font-bold text-green-50 lg:flex md:text-2xl">Meals</h1>

      <!-- Search Input -->
      <input
        type="search"
        v-model="searchQuery"
        placeholder="Search meal"
        class="hidden px-1 ml-auto border rounded-md lg:p-2 focus:outline-none focus:ring sm:flex md:flex focus:border-green-500"
      >

      <div class="flex space-x-2">
        <button
          @click="refreshMeals"
          class="px-1 py-1 text-white transition bg-green-600 rounded-md lg:px-2 hover:bg-green-700"
        >
          <i class="pi pi-refresh"></i>
        </button>

        <RouterLink to="/newmeal">
          <button
            class="px-1 py-1 ml-24 text-white transition bg-blue-600 rounded-md lg:px-2 hover:bg-blue-700 md:ml-0"
          >
            Create Meal
          </button>
        </RouterLink>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center h-64 md:ml-[30%] lg:ml-[20%] lg:pt-[20%]">
      <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
    </div>


    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center pt-40">
      <div class="p-6 bg-red-100 rounded-lg">
        <p class="text-red-600">{{ error }}</p>
        <button @click="fetchMeals" class="px-4 py-2 mt-4 text-white bg-red-500 rounded hover:bg-red-600">
          Try Again
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="meals.length === 0" class="flex items-center justify-center pt-40">
      <div class="p-6 text-center bg-white rounded-lg shadow-md">
        <i class="text-5xl text-gray-400 pi pi-inbox"></i>
        <p class="mt-4 text-lg text-gray-600">No meals found</p>
        <RouterLink to="/newmeal">
          <button class="px-4 py-2 mt-4 text-white bg-green-600 rounded hover:bg-green-700">
            Create Your First Meal
          </button>
        </RouterLink>
      </div>
    </div>

    <!-- Meal Cards -->
    <div v-else class="pt-20">
      <div class="px-12 mb-4 text-3xl font-bold">
        <h1>Meals</h1>
      </div>

      <div class="grid grid-cols-1 gap-5 mx-10 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 lg:ml-[21%] lg:mr-10">
        <div
          v-for="meal in filteredMeals"
          :key="meal.id"
          class="transition-transform duration-300 bg-white rounded-lg shadow-md hover:shadow-lg hover:scale-105"
        >
          <div class="relative p-5">
            <img :src="meal.image" alt="" class="object-cover w-full h-32 rounded-md">
            <!-- Meal Type Badge -->
            <span
              v-if="meal.mealType"
              class="absolute px-2 py-1 text-xs font-bold text-white rounded-full left-7 top-7"
              :class="meal.mealType === 'premium' ? 'bg-amber-500' : 'bg-green-600'"
            >
              {{ meal.mealType }}
            </span>
          </div>

          <div class="px-4">
            <h2 class="mt-2 text-lg font-bold">{{ meal.name }}</h2>
            <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ meal.description }}</p>
          </div>

          <hr class="mt-5">

          <div class="flex items-center p-3 space-x-2 bg-green-800 rounded-b-lg">
            <RouterLink :to="`/meal/${meal.id}`" class="flex-1">
              <button class="w-full px-2 py-1 text-white transition bg-green-600 rounded-md hover:bg-green-700">
                <i class="pi pi-eye"></i> View
              </button>
            </RouterLink>

            <RouterLink :to="`/edit-meal/${meal.id}`" class="flex-1">
              <button class="w-full px-2 py-1 text-white transition bg-blue-600 rounded-md hover:bg-blue-700">
                <i class="pi pi-pencil"></i> Edit
              </button>
            </RouterLink>

            <button
              @click="confirmDeleteMeal(meal)"
              class="flex-1 px-2 py-1 text-white transition bg-red-600 rounded-md hover:bg-red-700"
            >
              <i class="pi pi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>



    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Modal Backdrop -->
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="cancelDelete"></div>

      <!-- Modal Content -->
      <div class="relative w-full max-w-md p-6 mx-4 bg-white rounded-lg shadow-xl">
        <div class="flex flex-col items-center">
          <!-- Warning Icon -->
          <div class="flex items-center justify-center w-16 h-16 mb-4 text-red-500 bg-red-100 rounded-full">
            <i class="text-2xl pi pi-exclamation-triangle"></i>
          </div>

          <h3 class="mb-2 text-xl font-bold text-gray-800">Confirm Deletion</h3>

          <p class="mb-6 text-center text-gray-500">
            Are you sure you want to delete the meal
            <span class="font-semibold text-gray-700">{{ mealToDelete?.name }}</span>?
            This action cannot be undone.
          </p>

          <div class="flex w-full gap-3">
            <button
              @click="cancelDelete"
              class="flex-1 px-4 py-2 text-gray-700 transition bg-gray-200 rounded-md hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              @click="deleteMeal"
              class="flex-1 px-4 py-2 text-white transition bg-red-600 rounded-md hover:bg-red-700"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
