<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const mealId = route.params.id;

const meal = ref(null);
const isLoading = ref(true);
const error = ref(null);
const ingredients = ref([]);
const relatedMeals = ref([]);

// Fetch meal details from Parse
const fetchMealDetails = async () => {
  isLoading.value = true;
  error.value = null;

  try {
    // Create Parse query for the specific meal
    const Meal = Parse.Object.extend('Meals');
    const query = new Parse.Query(Meal);

    // Find meal by objectId
    const result = await query.get(mealId);

    if (!result) {
      throw new Error('Meal not found');
    }

    // Transform Parse object to plain object
    meal.value = {
      id: result.id,
      name: result.get('name'),
      price: result.get('mealType') === 'premium' ? 3000 : 2000, // Example price logic
      image: result.get('image') ? result.get('image').url() : new URL("@/assets/default-meal.png", import.meta.url).href,
      description: result.get('description') || 'No description available',
      mealType: result.get('mealType') || 'regular',
      schedule: result.get('schedule') ? new Date(result.get('schedule')).toLocaleDateString() : 'Flexible',
      note: result.get('note') || 'No special notes',
      items: result.get('items') || [],
      createdAt: result.createdAt ? new Date(result.createdAt).toLocaleDateString() : '',
      updatedAt: result.updatedAt ? new Date(result.updatedAt).toLocaleDateString() : '',
      parseObject: result // Store reference to original Parse object
    };

    // Fetch ingredients if they exist
    if (result.get('ingredients')) {
      try {
        const ingredientsRelation = result.get('ingredients');
        if (ingredientsRelation && typeof ingredientsRelation.query === 'function') {
          const ingredientsQuery = ingredientsRelation.query();
          const ingredientsResults = await ingredientsQuery.find();
          ingredients.value = ingredientsResults.map(ingredient => ({
            id: ingredient.id,
            name: ingredient.get('name'),
            quantity: ingredient.get('quantity'),
            unit: ingredient.get('unit') || ''
          }));
        }
      } catch (ingredientError) {
        console.error('Error fetching ingredients:', ingredientError);
        ingredients.value = [];
      }
    }

    // Fetch related meals (same meal type)
    try {
      const relatedQuery = new Parse.Query(Meal);
      relatedQuery.equalTo('mealType', meal.value.mealType);
      relatedQuery.notEqualTo('objectId', mealId);
      relatedQuery.limit(4);

      const relatedResults = await relatedQuery.find();
      relatedMeals.value = relatedResults.map(related => ({
        id: related.id,
        name: related.get('name'),
        image: related.get('image') ? related.get('image').url() : new URL("@/assets/default-meal.png", import.meta.url).href,
        mealType: related.get('mealType') || 'regular'
      }));
    } catch (relatedError) {
      console.error('Error fetching related meals:', relatedError);
      relatedMeals.value = [];
    }

  } catch (error) {
    console.error('Error fetching meal details:', error);
    error.value = 'Failed to load meal details. Please try again later.';
  } finally {
    isLoading.value = false;
  }
};


// Navigate back
const goBack = () => {
  router.back();
};

// Load data on component mount
onMounted(() => {
  fetchMealDetails();
});
</script>

<template>
  <div class="min-h-screen bg-green-50 md:ml-[20%]">
    <!-- Header with back button -->
    <div class="sticky top-0 z-10 flex items-center w-full p-4 bg-green-900 shadow-md">
      <button @click="goBack" class="flex items-center text-white hover:text-green-200">
        <i class="mr-2 pi pi-arrow-left"></i> Back to Meals
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center p-20">
      <div class="flex flex-col items-center">
        <div class="w-16 h-16 border-4 border-t-4 border-green-500 rounded-full animate-spin"></div>
        <p class="mt-4 text-lg">Loading meal details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center p-20">
      <div class="p-6 bg-red-100 rounded-lg">
        <p class="text-red-600">{{ error }}</p>
        <div class="flex mt-4 space-x-4">
          <button @click="fetchMealDetails" class="px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600">
            Try Again
          </button>
          <button @click="goBack" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
            Go Back
          </button>
        </div>
      </div>
    </div>

    <!-- Meal Details Content -->
    <div v-else-if="meal" class="container px-4 py-8 mx-auto lg:px-8">

      <!-- Hero Section -->
      <div class="overflow-hidden bg-white rounded-lg shadow-lg">
        <div class="relative h-64 md:h-80 lg:h-96">
          <img :src="meal.image" :alt="meal.name" class="object-cover w-full h-full">
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

          <!-- Meal Type Badge -->
          <span
            class="absolute px-3 py-1 text-sm font-bold text-white rounded-full top-4 right-4"
            :class="meal.mealType === 'premium' ? 'bg-amber-500' : 'bg-green-600'"
          >
            {{ meal.mealType }}
          </span>

          <!-- Meal Name -->
          <div class="absolute bottom-0 left-0 right-0 p-6">
            <h1 class="text-2xl font-bold text-white sm:text-3xl md:text-4xl">{{ meal.name }}</h1>
            <p class="mt-2 text-lg text-green-100">{{ meal.price }} XAF</p>
          </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-green-800 md:p-6">
          <div class="flex items-center space-x-2 text-white">
            <i class="pi pi-calendar"></i>
            <span>Schedule: {{ meal.schedule }}</span>
          </div>


        </div>
      </div>

      <!-- Content Sections -->
      <div class="grid gap-8 mt-8 md:grid-cols-3">
        <!-- Main Content -->
        <div class="md:col-span-2">
          <!-- Description -->
          <div class="p-6 mb-8 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold">Description</h2>
            <div class="mt-4 prose max-w-none">
              <p>{{ meal.description }}</p>
            </div>
          </div>

          <!-- Ingredients -->
          <div class="p-6 mb-8 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold">Ingredients</h2>
            <div v-if="ingredients.length > 0" class="mt-4">
              <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <li v-for="ingredient in ingredients" :key="ingredient.id" class="flex items-center p-2 rounded bg-green-50">
                  <i class="mr-2 text-green-600 pi pi-check-circle"></i>
                  <span>{{ ingredient.name }}</span>
                  <span v-if="ingredient.quantity" class="ml-auto font-medium">
                    {{ ingredient.quantity }} {{ ingredient.unit }}
                  </span>
                </li>
              </ul>
            </div>
            <div v-else class="p-4 mt-4 italic text-center text-gray-500 border border-gray-200 rounded">
              No ingredients information available
            </div>
          </div>

          <!-- Items (if available) -->
          <div v-if="meal.items && meal.items.length > 0" class="p-6 mb-8 bg-white rounded-lg shadow">
            <h2 class="text-xl font-bold">Meal Items</h2>
            <ul class="mt-4 space-y-2">
              <li v-for="(item, index) in meal.items" :key="index" class="flex items-center p-2 rounded bg-green-50">
                <i class="mr-2 text-green-600 pi pi-circle-fill"></i>
                <span>{{ item }}</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-8">
          <!-- Notes -->
          <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-lg font-bold">Special Notes</h2>
            <div class="p-4 mt-4 italic border-l-4 border-green-400 bg-green-50">
              {{ meal.note }}
            </div>
          </div>

          <!-- Meal Info -->
          <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-lg font-bold">Meal Information</h2>
            <ul class="mt-4 space-y-3">
              <li class="flex justify-between">
                <span class="text-gray-600">Created:</span>
                <span class="font-medium">{{ meal.createdAt }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-gray-600">Last Updated:</span>
                <span class="font-medium">{{ meal.updatedAt }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-gray-600">Meal Type:</span>
                <span
                  class="px-2 py-1 text-xs font-bold text-white rounded-full"
                  :class="meal.mealType === 'premium' ? 'bg-amber-500' : 'bg-green-600'"
                >
                  {{ meal.mealType }}
                </span>
              </li>
            </ul>
          </div>

          <!-- Related Meals -->
          <div v-if="relatedMeals.length > 0" class="p-6 bg-white rounded-lg shadow">
            <h2 class="text-lg font-bold">Similar Meals</h2>
            <div class="grid grid-cols-2 gap-3 mt-4">
              <div v-for="related in relatedMeals" :key="related.id" class="overflow-hidden rounded-lg cursor-pointer hover:opacity-90">
                <router-link :to="`/mealplan/${related.id}`">
                  <div class="relative h-24">
                    <img :src="related.image" :alt="related.name" class="object-cover w-full h-full">
                    <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/70 to-transparent">
                      <p class="p-2 text-xs font-medium text-white">{{ related.name }}</p>
                    </div>
                  </div>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
