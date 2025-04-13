<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const mealPlan = ref(null);
const loading = ref(true);
const error = ref(null);

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return 'Not set';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date);
};

// Calculate duration in days
const calculateDuration = (startDate, endDate) => {
  if (!startDate || !endDate) return 'Not specified';

  const start = new Date(startDate);
  const end = new Date(endDate);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  return diffDays === 1 ? '1 day' : `${diffDays} days`;
};

// Fetch meal plan by ID
const fetchMealPlan = async () => {
  const id = route.params.id;
  if (!id) {
    error.value = 'No meal plan ID provided';
    loading.value = false;
    return;
  }

  try {
    const MealPlan = Parse.Object.extend('MealPlans');
    const query = new Parse.Query(MealPlan);

    // Include related objects
    query.include('meal');
    query.include('user');

    const result = await query.get(id);
    const planData = result.toJSON();

    // Extract meal info if available
    if (planData.meal) {
      planData.mealDetails = {
        name: planData.meal.name,
        description: planData.meal.description,
        image: planData.meal.image ? planData.meal.image.url : null,
        items: planData.meal.items || []
      };
    }

    // Extract user info if available
    if (planData.user) {
      planData.userDetails = {
        name: planData.user.username || 'Unknown User',
        email: planData.user.email || ''
      };
    }

    mealPlan.value = planData;
  } catch (err) {
    console.error('Error fetching meal plan:', err);
    error.value = 'Failed to load meal plan. It may have been deleted or you may not have permission to view it.';
  } finally {
    loading.value = false;
  }
};

// Navigate to edit
const editMealPlan = () => {
  router.push(`/meal-plan/edit/${route.params.id}`);
};

// Navigate back to meal plans list
const goToMealPlans = () => {
  router.push('/meal-plans');
};

// Load meal plan on component mount
onMounted(() => {
  fetchMealPlan();
});
</script>

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
            @click="goToMealPlans"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            Back to Meal Plans
          </button>
        </div>
      </div>

      <!-- Meal plan details -->
      <div v-else-if="mealPlan" class="animate-fadeIn">
        <!-- Header with actions -->
        <div class="mb-6 overflow-hidden bg-white shadow-md rounded-xl">
          <div class="relative">
            <!-- Banner background with gradient overlay -->
            <div class="h-32 bg-gradient-to-r from-green-700 to-green-500">
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            </div>

            <!-- Content positioned over the banner -->
            <div class="absolute top-0 left-0 flex items-center justify-between w-full h-full px-6">
              <h1 class="text-3xl font-bold text-white">{{ mealPlan?.name }}</h1>

              <div class="flex space-x-3">
                <button
                  @click="editMealPlan"
                  class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                >
                  <i class="mr-2 pi pi-pencil"></i>
                  Edit
                </button>
                <button
                  @click="goToMealPlans"
                  class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                >
                  <i class="mr-2 pi pi-arrow-left"></i>
                  Back
                </button>
              </div>
            </div>
          </div>

          <!-- Plan information -->
          <div class="p-6">
            <!-- Duration info cards -->
            <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
              <div class="p-4 border border-green-100 rounded-lg bg-green-50">
                <h3 class="mb-1 text-sm font-medium text-green-800">Start Date</h3>
                <p class="font-semibold text-gray-700">
                  {{ mealPlan.startDate ? formatDate(mealPlan.startDate.iso) : 'Not set' }}
                </p>
              </div>

              <div class="p-4 border border-green-100 rounded-lg bg-green-50">
                <h3 class="mb-1 text-sm font-medium text-green-800">End Date</h3>
                <p class="font-semibold text-gray-700">
                  {{ mealPlan.endDate ? formatDate(mealPlan.endDate.iso) : 'Not set' }}
                </p>
              </div>

              <div class="p-4 border border-green-100 rounded-lg bg-green-50">
                <h3 class="mb-1 text-sm font-medium text-green-800">Duration</h3>
                <p class="font-semibold text-gray-700">
                  {{ calculateDuration(
                    mealPlan.startDate?.iso,
                    mealPlan.endDate?.iso
                  ) }}
                </p>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-6">
              <h2 class="mb-2 text-xl font-semibold text-gray-800">Description</h2>
              <p class="text-gray-600">
                {{ mealPlan.description || 'No description provided.' }}
              </p>
            </div>
            <!-- Meal details -->
            <div class="mb-6">
              <h2 class="mb-2 text-xl font-semibold text-gray-800">Meal Details</h2>
              <div v-if="mealPlan.mealDetails" class="flex items-center space-x-4">
                <img
                  :src="mealPlan.mealDetails.image"
                  alt=""
                  class="w-32 h-32 rounded-lg shadow-md"
                >
                <div>
                  <h3 class="text-lg font-bold text-gray-800">{{ mealPlan.mealDetails.name }}</h3>
                  <p class="text-gray-600">{{ mealPlan.mealDetails.description }}</p>
                </div>
              </div>
              <div v-else>
                <p class="text-gray-600">No meal details available.</p>
              </div>
            </div>
            <!-- User details -->
            <div class="mb-6">
              <h2 class="mb-2 text-xl font-semibold text-gray-800">User Details</h2>
              <p class="text-gray-600">
                {{ mealPlan.userDetails?.name || 'Unknown User' }}
                ({{ mealPlan.userDetails?.email || 'No email provided' }})
              </p>
            </div>
            <!-- Meal items -->
            <div class="mb-6">
              <h2 class="mb-2 text-xl font-semibold text-gray-800">Meal Items</h2>
              <ul class="pl-5 list-disc">
                <li v-for="item in mealPlan.mealDetails?.items" :key="item.id" class="text-gray-600">
                  {{ item.name }} - {{ item.quantity }} units
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

