<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const success = ref(false);
const users = ref([]);
const meals = ref([]);

// Form data
const mealPlan = ref({
  name: '',
  description: '',
  startDate: null,
  endDate: null,
  meal: null,
  user: null
});

// Validation errors
const validationErrors = ref({
  name: '',
  startDate: '',
  endDate: '',
  meal: '',
  user: ''
});

// Date validation and formatting
const validateDates = () => {
  validationErrors.value.startDate = '';
  validationErrors.value.endDate = '';

  if (mealPlan.value.startDate && mealPlan.value.endDate) {
    const start = new Date(mealPlan.value.startDate);
    const end = new Date(mealPlan.value.endDate);

    if (end < start) {
      validationErrors.value.endDate = 'End date cannot be before start date';
      return false;
    }
  }

  return true;
};

// Validate form
const validateForm = () => {
  let isValid = true;

  // Reset validation errors
  Object.keys(validationErrors.value).forEach(key => {
    validationErrors.value[key] = '';
  });

  // Validate required fields
  if (!mealPlan.value.name.trim()) {
    validationErrors.value.name = 'Meal plan name is required';
    isValid = false;
  }

  if (!mealPlan.value.startDate) {
    validationErrors.value.startDate = 'Start date is required';
    isValid = false;
  }

  if (!mealPlan.value.endDate) {
    validationErrors.value.endDate = 'End date is required';
    isValid = false;
  }

  if (!mealPlan.value.meal) {
    validationErrors.value.meal = 'Please select a meal';
    isValid = false;
  }

  if (!mealPlan.value.user) {
    validationErrors.value.user = 'Please select a user';
    isValid = false;
  }

  // Validate dates
  if (isValid && !validateDates()) {
    isValid = false;
  }

  return isValid;
};

// Fetch users
const fetchUsers = async () => {
  try {

    const results = await Parse.Cloud.run('getAllUsers');
    users.value = results;
  } catch (err) {
    console.error('Error fetching users:', err);
    error.value = 'Failed to load users. Please try again later.';
  }
};

// Fetch meals
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

// Create new meal plan
const createMealPlan = async () => {
  if (!validateForm()) {
    window.scrollTo(0, 0);
    return;
  }

  saving.value = true;
  error.value = null;

  try {
    // Create new meal plan object
    const MealPlan = Parse.Object.extend('MealPlans');
    const newMealPlan = new MealPlan();

    // Set basic fields
    newMealPlan.set('name', mealPlan.value.name);
    newMealPlan.set('description', mealPlan.value.description);

    // Set dates
    if (mealPlan.value.startDate) {
      newMealPlan.set('startDate', new Date(mealPlan.value.startDate));
    }

    if (mealPlan.value.endDate) {
      newMealPlan.set('endDate', new Date(mealPlan.value.endDate));
    }

    // Set pointer to meal
    if (mealPlan.value.meal) {
      const mealPointer = Parse.Object.extend('Meals').createWithoutData(mealPlan.value.meal);
      newMealPlan.set('meal', mealPointer);
    }

    // PROPERLY set pointer to user
    if (mealPlan.value.user) {
      // This is the correct way to create a user pointer
      const userPointer = Parse.User.createWithoutData(mealPlan.value.user);
      newMealPlan.set('user', userPointer);

      // Also set ACL to ensure proper permissions
      const acl = new Parse.ACL();
      acl.setPublicReadAccess(true); // Adjust as needed
      acl.setWriteAccess(userPointer, true); // Allow the assigned user to modify
      acl.setReadAccess(userPointer, true); // Allow the assigned user to read
      newMealPlan.setACL(acl);
    }

    // Save to database
    await newMealPlan.save();

    // Show success and reset form
    success.value = true;

    // Navigate to the view page after a brief delay
    setTimeout(() => {
      router.push(`/meal-plan/${newMealPlan.id}`);
    }, 1500);

  } catch (err) {
    console.error('Error creating meal plan:', err);
    error.value = `Failed to create meal plan: ${err.message}`;
    window.scrollTo(0, 0);
  } finally {
    saving.value = false;
  }
};

// Cancel and go back
const cancelCreate = () => {
  router.push('/meal-plan');
};

// Load data on component mount
onMounted(async () => {
  loading.value = true;

  try {
    await Promise.all([fetchUsers(), fetchMeals()]);
  } catch (err) {
    console.error('Error initializing page:', err);
    error.value = 'There was a problem loading the page. Please try again.';
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

      <div v-else class="animate-fadeIn">
        <!-- Header -->
        <div class="mb-6 overflow-hidden bg-white shadow-md rounded-xl">
          <div class="relative">
            <!-- Banner background with gradient overlay -->
            <div class="h-32 bg-gradient-to-r from-green-700 to-green-500">
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            </div>

            <!-- Content positioned over the banner -->
            <div class="absolute top-0 left-0 flex items-center justify-between w-full h-full px-6">
              <h1 class="text-3xl font-bold text-white">Create New Meal Plan</h1>

              <div>
                <button
                  @click="cancelCreate"
                  class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                >
                  <i class="mr-2 pi pi-arrow-left"></i>
                  Back
                </button>
              </div>
            </div>
          </div>

          <!-- Form content -->
          <div class="p-6">
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

            <!-- Success message -->
            <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
              <div class="flex items-center">
                <i class="mr-3 text-2xl pi pi-check-circle"></i>
                <div>
                  <h3 class="font-medium">Success</h3>
                  <p>Meal plan created successfully! Redirecting...</p>
                </div>
              </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="createMealPlan">
              <!-- Basic Information section -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Basic Information</h2>

                <!-- Name -->
                <div class="mb-4">
                  <label for="name" class="block mb-2 font-medium text-gray-700">
                    Meal Plan Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="name"
                    v-model="mealPlan.name"
                    type="text"
                    class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    :class="{'border-red-500': validationErrors.name}"
                    placeholder="Enter meal plan name"
                  >
                  <p v-if="validationErrors.name" class="mt-1 text-sm text-red-600">
                    {{ validationErrors.name }}
                  </p>
                </div>

                <!-- Description -->
                <div class="mb-4">
                  <label for="description" class="block mb-2 font-medium text-gray-700">
                    Description
                  </label>
                  <textarea
                    id="description"
                    v-model="mealPlan.description"
                    rows="3"
                    class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter a description for this meal plan"
                  ></textarea>
                </div>
              </div>

              <!-- Dates section -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Schedule</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <!-- Start Date -->
                  <div>
                    <label for="startDate" class="block mb-2 font-medium text-gray-700">
                      Start Date <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="startDate"
                      v-model="mealPlan.startDate"
                      type="date"
                      class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                      :class="{'border-red-500': validationErrors.startDate}"
                    >
                    <p v-if="validationErrors.startDate" class="mt-1 text-sm text-red-600">
                      {{ validationErrors.startDate }}
                    </p>
                  </div>

                  <!-- End Date -->
                  <div>
                    <label for="endDate" class="block mb-2 font-medium text-gray-700">
                      End Date <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="endDate"
                      v-model="mealPlan.endDate"
                      type="date"
                      class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                      :class="{'border-red-500': validationErrors.endDate}"
                    >
                    <p v-if="validationErrors.endDate" class="mt-1 text-sm text-red-600">
                      {{ validationErrors.endDate }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Meal selection section -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">Meal Selection</h2>

                <div class="mb-4">
                  <label for="meal" class="block mb-2 font-medium text-gray-700">
                    Select Meal <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="meal"
                    v-model="mealPlan.meal"
                    class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    :class="{'border-red-500': validationErrors.meal}"
                  >
                    <option value="">-- Select a meal --</option>
                    <option v-for="meal in meals" :key="meal.id" :value="meal.id">
                      {{ meal.name }}
                    </option>
                  </select>
                  <p v-if="validationErrors.meal" class="mt-1 text-sm text-red-600">
                    {{ validationErrors.meal }}
                  </p>
                </div>

                <!-- Preview selected meal -->
                <div v-if="mealPlan.meal" class="p-4 mt-2 border border-green-100 rounded-lg bg-green-50">
                  <div class="flex items-center space-x-4">
                    <img
                      :src="meals.find(m => m.id === mealPlan.meal)?.image || '/placeholder-meal.jpg'"
                      alt="Meal image"
                      class="object-cover w-24 h-24 rounded-lg shadow-sm"
                    >
                    <div>
                      <h3 class="font-semibold text-gray-800">
                        {{ meals.find(m => m.id === mealPlan.meal)?.name }}
                      </h3>
                      <p class="text-sm text-gray-600">
                        {{ meals.find(m => m.id === mealPlan.meal)?.description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- User assignment section -->
              <div class="mb-6">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">User Assignment</h2>

                <div class="mb-4">
                  <label for="user" class="block mb-2 font-medium text-gray-700">
                    Assign to User <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="user"
                    v-model="mealPlan.user"
                    class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                    :class="{'border-red-500': validationErrors.user}"
                  >
                    <option value="">-- Select a user --</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} <span v-if="user.email">({{ user.email }})</span>
                    </option>
                  </select>
                  <p v-if="validationErrors.user" class="mt-1 text-sm text-red-600">
                    {{ validationErrors.user }}
                  </p>
                </div>
              </div>

              <!-- Form actions -->
              <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                <button
                  type="submit"
                  class="flex items-center justify-center px-6 py-3 font-medium text-white transition duration-200 bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
                  :disabled="saving"
                >
                  <i class="mr-2 pi pi-save" v-if="!saving"></i>
                  <i class="mr-2 pi pi-spinner animate-spin" v-else></i>
                  {{ saving ? 'Creating...' : 'Create Meal Plan' }}
                </button>

                <button
                  type="button"
                  @click="cancelCreate"
                  class="flex items-center justify-center px-6 py-3 font-medium text-gray-700 transition duration-200 border border-gray-300 rounded-lg hover:bg-gray-100"
                  :disabled="saving"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
