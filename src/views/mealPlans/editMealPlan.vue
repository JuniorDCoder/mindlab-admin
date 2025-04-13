<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(false);
const users = ref([]);
const meals = ref([]);
const originalPlan = ref(null);

// Editable meal plan data
const mealPlan = ref({
  id: '',
  name: '',
  description: '',
  startDate: '',
  endDate: '',
  userId: '',
  mealId: ''
});

// Validation state
const validation = ref({
  name: true,
  startDate: true,
  endDate: true,
  userId: true,
  mealId: true
});

// Format date for input fields (YYYY-MM-DD)
const formatDateForInput = (dateObj) => {
  if (!dateObj) return '';
  const date = new Date(dateObj);
  return date.toISOString().split('T')[0];
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
    originalPlan.value = result;

    // Extract data for the form
    mealPlan.value = {
      id: result.id,
      name: result.get('name') || '',
      description: result.get('description') || '',
      startDate: result.get('startDate') ? formatDateForInput(result.get('startDate')) : '',
      endDate: result.get('endDate') ? formatDateForInput(result.get('endDate')) : '',
      userId: result.get('user') ? result.get('user').id : '',
      mealId: result.get('meal') ? result.get('meal').id : ''
    };

  } catch (err) {
    console.error('Error fetching meal plan:', err);
    error.value = 'Failed to load meal plan. It may have been deleted or you may not have permission to view it.';
  }
};

// Fetch users for dropdown
const fetchUsers = async () => {
  try {
    const User = Parse.Object.extend('_User');
    const query = new Parse.Query(User);
    query.limit(1000); // Set a reasonable limit
    const results = await query.find();

    users.value = results.map(user => ({
      id: user.id,
      name: user.get('username') || 'Unknown User',
      email: user.get('email') || ''
    }));

    // Log how many users we found
    console.log(`Fetched ${results.length} users`);

  } catch (err) {
    console.error('Error fetching users:', err);
    error.value = 'Failed to load users. Please try again later.';
  }
};

// Fetch meals for dropdown
const fetchMeals = async () => {
  try {
    const Meal = Parse.Object.extend('Meals');
    const query = new Parse.Query(Meal);
    const results = await query.find();

    meals.value = results.map(meal => ({
      id: meal.id,
      name: meal.get('name') || 'Unnamed Meal',
      description: meal.get('description') || '',
      image: meal.get('image') ? meal.get('image').url() : null
    }));
  } catch (err) {
    console.error('Error fetching meals:', err);
    error.value = 'Failed to load meals. Please try again later.';
  }
};

// Validate form fields
const validateForm = () => {
  let isValid = true;
  validation.value = {
    name: !!mealPlan.value.name,
    startDate: !!mealPlan.value.startDate,
    endDate: !!mealPlan.value.endDate,
    userId: !!mealPlan.value.userId,
    mealId: !!mealPlan.value.mealId
  };

  // Check if all validation fields are true
  for (const field in validation.value) {
    if (!validation.value[field]) {
      isValid = false;
    }
  }

  // Check if end date is after start date
  if (mealPlan.value.startDate && mealPlan.value.endDate) {
    const start = new Date(mealPlan.value.startDate);
    const end = new Date(mealPlan.value.endDate);
    if (end < start) {
      validation.value.endDate = false;
      isValid = false;
      error.value = 'End date must be after start date';
    }
  }

  return isValid;
};

// Submit the form
const updateMealPlan = async () => {
  error.value = null;
  success.value = false;

  if (!validateForm()) {
    error.value = 'Please fill in all required fields correctly';
    return;
  }

  saving.value = true;

  try {
    // Set values on the original Parse object
    originalPlan.value.set('name', mealPlan.value.name);
    originalPlan.value.set('description', mealPlan.value.description);

    // Handle dates
    if (mealPlan.value.startDate) {
      originalPlan.value.set('startDate', new Date(mealPlan.value.startDate));
    }

    if (mealPlan.value.endDate) {
      originalPlan.value.set('endDate', new Date(mealPlan.value.endDate));
    }

    // Set pointer to user
    if (mealPlan.value.user) {
      const User = Parse.Object.extend('_User');
      const userPointer = new User();
      userPointer.id = mealPlan.value.user;
      originalPlan.value.set('user', userPointer);
    }else {
      // If no user selected, remove the pointer
      originalPlan.value.unset('user');
    }

    if (mealPlan.value.mealId) {
      const mealPointer = Parse.Object.extend('Meals').createWithoutData(mealPlan.value.mealId);
      originalPlan.value.set('meal', mealPointer);
    }

    // Save to database
    await originalPlan.value.save();

    success.value = true;
    setTimeout(() => {
      router.push(`/meal-plan/${mealPlan.value.id}`);
    }, 3500);
  } catch (err) {
    console.error('Error updating meal plan:', err);
    console.error('Details:', {
      name: mealPlan.value.name,
      userId: mealPlan.value.userId,
      mealId: mealPlan.value.mealId
    });
    error.value = 'Failed to update meal plan. Please try again.';
  } finally {
    saving.value = false;
  }
};

// Navigate back to view meal plan
const cancel = () => {
  router.push(`/meal-plan/${mealPlan.value.id}`);
};

// Load data on component mount
onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([fetchMealPlan(), fetchUsers(), fetchMeals()]);
  } catch (err) {
    console.error('Error initializing component:', err);
    error.value = 'Failed to initialize component. Please try again.';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error state (if meal plan not found) -->
      <div v-else-if="error && !mealPlan.id" class="p-8 bg-white shadow-md rounded-xl">
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
            @click="() => router.push('/meal-plans')"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            Back to Meal Plans
          </button>
        </div>
      </div>

      <!-- Edit form -->
      <div v-else-if="mealPlan.id" class="animate-fadeIn">
        <!-- Header -->
        <div class="mb-6 overflow-hidden bg-white shadow-md rounded-xl">
          <div class="relative">
            <!-- Banner background with gradient overlay -->
            <div class="h-32 bg-gradient-to-r from-green-700 to-green-500">
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            </div>

            <!-- Content positioned over the banner -->
            <div class="absolute top-0 left-0 flex items-center justify-between w-full h-full px-6">
              <h1 class="text-3xl font-bold text-white">Edit Meal Plan</h1>

              <div class="flex space-x-3">
                <button
                  @click="cancel"
                  class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                >
                  <i class="mr-2 pi pi-times"></i>
                  Cancel
                </button>
              </div>
            </div>
          </div>

          <!-- Form -->
          <div class="p-6">
            <!-- Success message -->
            <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
              <div class="flex items-center">
                <i class="mr-3 text-2xl pi pi-check-circle"></i>
                <div>
                  <h3 class="font-medium">Success!</h3>
                  <p>Your meal plan has been updated successfully. Redirecting...</p>
                </div>
              </div>
            </div>

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

            <form @submit.prevent="updateMealPlan">
              <!-- Basic Information -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Basic Information</h2>

                <div class="mb-4">
                  <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                    Plan Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="name"
                    v-model="mealPlan.name"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    :class="{ 'border-red-500': !validation.name }"
                    placeholder="Enter meal plan name"
                  >
                  <p v-if="!validation.name" class="mt-1 text-sm text-red-500">
                    Plan name is required
                  </p>
                </div>

                <div class="mb-4">
                  <label for="description" class="block mb-2 text-sm font-medium text-gray-700">
                    Description
                  </label>
                  <textarea
                    id="description"
                    v-model="mealPlan.description"
                    rows="3"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter plan description (optional)"
                  ></textarea>
                </div>
              </div>

              <!-- Dates -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Schedule</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div>
                    <label for="startDate" class="block mb-2 text-sm font-medium text-gray-700">
                      Start Date <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="startDate"
                      v-model="mealPlan.startDate"
                      type="date"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      :class="{ 'border-red-500': !validation.startDate }"
                    >
                    <p v-if="!validation.startDate" class="mt-1 text-sm text-red-500">
                      Start date is required
                    </p>
                  </div>

                  <div>
                    <label for="endDate" class="block mb-2 text-sm font-medium text-gray-700">
                      End Date <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="endDate"
                      v-model="mealPlan.endDate"
                      type="date"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      :class="{ 'border-red-500': !validation.endDate }"
                    >
                    <p v-if="!validation.endDate" class="mt-1 text-sm text-red-500">
                      End date is required and must be after start date
                    </p>
                  </div>
                </div>
              </div>

              <!-- Relationships -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Assignments</h2>

                <div class="mb-4">
                  <label for="userId" class="block mb-2 text-sm font-medium text-gray-700">
                    Assign to User <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="userId"
                    v-model="mealPlan.userId"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    :class="{ 'border-red-500': !validation.userId }"
                  >
                    <option value="" disabled>Select a user</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} ({{ user.email }})
                    </option>
                  </select>
                  <p v-if="!validation.userId" class="mt-1 text-sm text-red-500">
                    Please select a user
                  </p>
                </div>

                <div class="mb-4">
                  <label for="mealId" class="block mb-2 text-sm font-medium text-gray-700">
                    Select Meal <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="mealId"
                    v-model="mealPlan.mealId"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    :class="{ 'border-red-500': !validation.mealId }"
                  >
                    <option value="" disabled>Select a meal</option>
                    <option v-for="meal in meals" :key="meal.id" :value="meal.id">
                      {{ meal.name }}
                    </option>
                  </select>
                  <p v-if="!validation.mealId" class="mt-1 text-sm text-red-500">
                    Please select a meal
                  </p>
                </div>

                <!-- Preview selected meal -->
                <div v-if="mealPlan.mealId" class="p-4 mt-4 border border-green-100 rounded-lg bg-green-50">
                  <h3 class="mb-2 text-lg font-semibold text-gray-800">Selected Meal Preview</h3>
                  <div v-if="meals.find(m => m.id === mealPlan.mealId)" class="flex items-center space-x-4">
                    <img
                      :src="meals.find(m => m.id === mealPlan.mealId).image || '/placeholder-meal.jpg'"
                      alt="Meal preview"
                      class="w-20 h-20 rounded-md shadow-sm"
                    >
                    <div>
                      <p class="font-medium text-gray-800">
                        {{ meals.find(m => m.id === mealPlan.mealId).name }}
                      </p>
                      <p class="text-sm text-gray-600">
                        {{ meals.find(m => m.id === mealPlan.mealId).description || 'No description available' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submit buttons -->
              <div class="flex justify-end space-x-4">
                <button
                  type="button"
                  @click="cancel"
                  class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex items-center px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50"
                >
                  <div v-if="saving" class="w-5 h-5 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></div>
                  {{ saving ? 'Saving...' : 'Update Meal Plan' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
