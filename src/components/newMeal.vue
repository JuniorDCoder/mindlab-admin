<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Define meal data structure based on Parse Back4App model
const mealData = reactive({
  name: '',
  description: '',
  mealType: 'breakfast',
  items: [],
  schedule: '',
  note: ''
});

// Available meal types
const mealTypes =['breakfast', 'lunch', 'dinner', 'snack', 'premium'];

// For dynamically adding meal items
const newItem = ref('');

// Handle image input
const imageFile = ref(null);
const imagePreview = ref(null);

const handleImageSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    imageFile.value = file;

    // Create preview URL
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

// Add a new item to the meal
const addItem = () => {
  if (newItem.value.trim()) {
    mealData.items.push(newItem.value.trim());
    newItem.value = '';
  }
};

// Remove an item from the meal
const removeItem = (index) => {
  mealData.items.splice(index, 1);
};

// Form validation
const validateForm = () => {
  if (!mealData.name.trim()) {
    errorMessage.value = 'Please enter a meal name';
    return false;
  }

  if (!mealData.description.trim()) {
    errorMessage.value = 'Please enter a meal description';
    return false;
  }

  return true;
};

// Create new meal in Parse Back4App
const saveMeal = async () => {
  // Reset messages
  errorMessage.value = '';
  successMessage.value = '';

  // Validate form
  if (!validateForm()) return;

  isSubmitting.value = true;

  try {
    // Create a new Parse object for Meals class
    const Meal = Parse.Object.extend('Meals');
    const meal = new Meal();

    // Set values on the meal object
    meal.set('name', mealData.name);
    meal.set('description', mealData.description);
    meal.set('mealType', mealData.mealType);
    meal.set('items', mealData.items);
    meal.set('note', mealData.note);

    // Handle schedule date if provided
    if (mealData.schedule) {
      meal.set('schedule', new Date(mealData.schedule));
    }

    // Handle image upload if selected
    if (imageFile.value) {
      const parseFile = new Parse.File(imageFile.value.name, imageFile.value);
      await parseFile.save();
      meal.set('image', parseFile);
    }

    // Save meal to Parse
    await meal.save();

    // Show success message
    successMessage.value = 'Meal created successfully!';

    // Redirect to meals list after a short delay
    setTimeout(() => {
      router.push('/meals');
    }, 1200);

  } catch (error) {
    console.error('Error creating meal:', error);
    errorMessage.value = `Failed to create meal: ${error.message}`;
  } finally {
    isSubmitting.value = false;
  }
};

// Cancel and go back
const cancelCreate = () => {
  router.push('/meals');
};
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50">
    <div class="max-w-3xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-green-800">Create New Meal</h1>
        <button @click="cancelCreate" class="flex items-center text-green-700 hover:text-green-900">
          <i class="mr-2 pi pi-arrow-left"></i> Back to Meals
        </button>
      </div>

      <!-- Main Form Card -->
      <div class="overflow-hidden bg-white shadow-lg rounded-xl">
        <!-- Form Messages -->
        <div v-if="errorMessage" class="p-4 border-l-4 border-red-500 bg-red-50">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="text-red-500 pi pi-times-circle"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <div v-if="successMessage" class="p-4 border-l-4 border-green-500 bg-green-50">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="text-green-500 pi pi-check-circle"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm text-green-700">{{ successMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="saveMeal" class="p-6 md:p-8">
          <div class="grid gap-6 md:grid-cols-2">
            <!-- Left Column -->
            <div class="space-y-6">
              <!-- Meal Name -->
              <div>
                <label for="name" class="block mb-1 text-sm font-medium text-gray-700">
                  Meal Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="mealData.name"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter meal name"
                >
              </div>

              <!-- Meal Type -->
              <div>
                <label for="mealType" class="block mb-1 text-sm font-medium text-gray-700">
                  Meal Type
                </label>
                <select
                  id="mealType"
                  v-model="mealData.mealType"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
                  <option v-for="type in mealTypes" :key="type" :value="type">
                    {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                  </option>
                </select>
              </div>

              <!-- Schedule Date -->
              <div>
                <label for="schedule" class="block mb-1 text-sm font-medium text-gray-700">
                  Schedule Date
                </label>
                <input
                  id="schedule"
                  v-model="mealData.schedule"
                  type="date"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
              </div>

              <!-- Description -->
              <div>
                <label for="description" class="block mb-1 text-sm font-medium text-gray-700">
                  Description <span class="text-red-500">*</span>
                </label>
                <textarea
                  id="description"
                  v-model="mealData.description"
                  rows="5"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  placeholder="Describe this meal..."
                ></textarea>
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
              <!-- Image Upload -->
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">
                  Meal Image
                </label>
                <div class="mt-1">
                  <!-- Image preview -->
                  <div v-if="imagePreview" class="w-full h-48 mb-3 overflow-hidden bg-gray-100 rounded-lg">
                    <img
                      :src="imagePreview"
                      alt="Meal preview"
                      class="object-cover w-full h-full"
                    >
                  </div>
                  <div v-else class="flex items-center justify-center w-full h-48 mb-3 bg-gray-100 border-2 border-gray-300 border-dashed rounded-lg">
                    <div class="p-4 text-center">
                      <i class="text-3xl text-gray-400 pi pi-image"></i>
                      <p class="mt-1 text-gray-500">Upload meal image</p>
                    </div>
                  </div>

                  <!-- File input -->
                  <label class="w-full cursor-pointer">
                    <div class="px-4 py-2 text-center text-green-700 transition border border-green-300 rounded-lg bg-green-50 hover:bg-green-100">
                      <i class="mr-2 pi pi-upload"></i>
                      {{ imagePreview ? 'Change Image' : 'Upload Image' }}
                    </div>
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="handleImageSelect"
                    >
                  </label>
                </div>
              </div>

              <!-- Special Notes -->
              <div>
                <label for="note" class="block mb-1 text-sm font-medium text-gray-700">
                  Special Notes
                </label>
                <textarea
                  id="note"
                  v-model="mealData.note"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                  placeholder="Any special notes about this meal..."
                ></textarea>
              </div>

              <!-- Meal Items -->
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">
                  Meal Items
                </label>
                <div class="flex mb-2 space-x-2">
                  <input
                    v-model="newItem"
                    type="text"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Add an item"
                    @keyup.enter="addItem"
                  >
                  <button
                    type="button"
                    @click="addItem"
                    class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
                  >
                    Add
                  </button>
                </div>

                <div v-if="mealData.items.length > 0" class="border border-gray-200 rounded-lg bg-gray-50">
                  <ul class="overflow-y-auto divide-y divide-gray-200 max-h-40">
                    <li v-for="(item, index) in mealData.items" :key="index" class="flex items-center justify-between px-4 py-2">
                      <span>{{ item }}</span>
                      <button
                        type="button"
                        @click="removeItem(index)"
                        class="text-red-500 hover:text-red-700"
                      >
                        <i class="pi pi-times"></i>
                      </button>
                    </li>
                  </ul>
                </div>
                <p v-else class="py-3 text-sm italic text-center text-gray-500 border border-gray-200 rounded-lg bg-gray-50">
                  No items added yet
                </p>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex flex-col justify-end mt-8 space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
            <button
              type="button"
              @click="cancelCreate"
              class="px-6 py-2 text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="flex items-center justify-center px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="w-4 h-4 mr-2 border-2 border-t-2 border-white rounded-full animate-spin"></span>
              {{ isSubmitting ? 'Creating...' : 'Create Meal' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animation for form elements */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

input, select, textarea {
  animation: fadeIn 0.5s ease-out;
  transition: all 0.3s ease;
}

input:focus, select:focus, textarea:focus {
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}
</style>
