<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const mealId = route.params.id;

const isLoading = ref(true);
const isSaving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Define meal data structure based on Parse attributes
const mealData = ref({
  name: '',
  description: '',
  mealType: 'snack',
  image: null,
  schedule: null,
  note: '',
  items: [],
  ingredients: []
});

// Options for meal types
const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'premium'];

// Fetch meal details from Parse
const fetchMealDetails = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    // Create Parse query for the specific meal
    const Meal = Parse.Object.extend('Meals');
    const query = new Parse.Query(Meal);

    // Find meal by objectId
    const result = await query.get(mealId);

    if (!result) {
      throw new Error('Meal not found');
    }

    // Store original Parse object reference for later updates
    mealData.value.parseObject = result;

    // Populate form with existing data
    mealData.value.name = result.get('name') || '';
    mealData.value.description = result.get('description') || '';
    mealData.value.mealType = result.get('mealType') || 'regular';
    mealData.value.note = result.get('note') || '';
    mealData.value.items = result.get('items') || [];

    // Handle date fields
    const scheduleDate = result.get('schedule');
    if (scheduleDate) {
      const formattedDate = new Date(scheduleDate).toISOString().split('T')[0];
      mealData.value.schedule = formattedDate;
    }

    // Store image info if exists
    const imageFile = result.get('image');
    if (imageFile) {
      mealData.value.imageUrl = imageFile.url();
      mealData.value.existingImageName = imageFile.name();
    }

  } catch (error) {
    console.error('Error fetching meal details:', error);
    errorMessage.value = 'Failed to load meal details. Please try again later.';
  } finally {
    isLoading.value = false;
  }
};

// Add a new item to the items array
const addItem = () => {
  if (newItem.value.trim()) {
    mealData.value.items.push(newItem.value.trim());
    newItem.value = '';
  }
};

// Remove an item from the items array
const removeItem = (index) => {
  mealData.value.items.splice(index, 1);
};

// Handle image selection
const imageFile = ref(null);
const imagePreview = ref(null);
const newItem = ref('');

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

// Save updated meal to Parse
const saveMeal = async () => {
  isSaving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    // Get the Parse object reference
    const mealObject = mealData.value.parseObject;

    // Update fields
    mealObject.set('name', mealData.value.name);
    mealObject.set('description', mealData.value.description);
    mealObject.set('mealType', mealData.value.mealType);
    mealObject.set('note', mealData.value.note);
    mealObject.set('items', mealData.value.items);

    // Handle schedule date if provided
    if (mealData.value.schedule) {
      mealObject.set('schedule', new Date(mealData.value.schedule));
    }

    // Handle image upload if a new image was selected
    if (imageFile.value) {
      const parseFile = new Parse.File(imageFile.value.name, imageFile.value);
      await parseFile.save();
      mealObject.set('image', parseFile);
    }

    // Save the updated object
    await mealObject.save();

    successMessage.value = 'Meal updated successfully!';

    // Navigate back after a short delay
    setTimeout(() => {
      router.push('/meals');
    }, 1500);

  } catch (error) {
    console.error('Error updating meal:', error);
    errorMessage.value = 'Failed to update meal. Please try again.';
  } finally {
    isSaving.value = false;
  }
};

// Go back to meals list
const goBack = () => {
  router.push('/meals');
};

// Load data when component mounts
onMounted(() => {
  fetchMealDetails();
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%]">
    <!-- Page Header -->
    <div class="flex items-center justify-between max-w-4xl mx-auto mb-6">
      <h1 class="text-2xl font-bold text-green-800">Edit Meal</h1>
      <button @click="goBack" class="flex items-center px-4 py-2 text-green-800 hover:text-green-600">
        <i class="mr-2 pi pi-arrow-left"></i> Back to Meals
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center max-w-4xl p-8 mx-auto bg-white shadow-md rounded-xl">
      <div class="flex flex-col items-center">
        <div class="w-12 h-12 border-4 border-t-4 border-green-500 rounded-full animate-spin"></div>
        <p class="mt-4 text-gray-600">Loading meal data...</p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-else-if="errorMessage" class="max-w-4xl p-8 mx-auto bg-white shadow-md rounded-xl">
      <div class="p-4 mb-6 text-red-700 border-l-4 border-red-500 bg-red-50">
        <p>{{ errorMessage }}</p>
      </div>
      <button @click="goBack" class="w-full py-3 text-white bg-gray-500 rounded-lg hover:bg-gray-600">
        Return to Meals
      </button>
    </div>

    <!-- Edit Form -->
    <div v-else class="max-w-4xl mx-auto overflow-hidden bg-white shadow-md rounded-xl">
      <!-- Success Message -->
      <div v-if="successMessage" class="p-4 mb-4 text-green-700 border-l-4 border-green-500 bg-green-50">
        <p>{{ successMessage }}</p>
      </div>

      <form @submit.prevent="saveMeal" class="p-6 lg:p-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Left Column -->
          <div class="space-y-6">
            <!-- Meal Name -->
            <div>
              <label for="name" class="block mb-1 text-sm font-medium text-gray-700">Meal Name <span class="text-red-500">*</span></label>
              <input
                id="name"
                v-model="mealData.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Enter meal name"
              >
            </div>

            <!-- Meal Type -->
            <div class="grid grid-cols-2 gap-4">

              <div>
                <label for="mealType" class="block mb-1 text-sm font-medium text-gray-700">Meal Type</label>
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
            </div>

            <!-- Schedule -->
            <div>
              <label for="schedule" class="block mb-1 text-sm font-medium text-gray-700">Schedule Date</label>
              <input
                id="schedule"
                v-model="mealData.schedule"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              >
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="block mb-1 text-sm font-medium text-gray-700">Description</label>
              <textarea
                id="description"
                v-model="mealData.description"
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                placeholder="Describe the meal..."
              ></textarea>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-6">
            <!-- Image -->
            <div>
              <label class="block mb-1 text-sm font-medium text-gray-700">Meal Image</label>
              <div class="flex flex-col items-center mt-1">
                <!-- Image preview -->
                <div v-if="imagePreview || mealData.imageUrl" class="w-full h-48 mb-3 overflow-hidden bg-gray-100 rounded-lg">
                  <img
                    :src="imagePreview || mealData.imageUrl"
                    alt="Meal preview"
                    class="object-cover w-full h-full"
                  >
                </div>
                <div v-else class="flex items-center justify-center w-full h-48 mb-3 bg-gray-100 rounded-lg">
                  <span class="text-gray-400">No image selected</span>
                </div>

                <!-- File input -->
                <label class="w-full cursor-pointer">
                  <div class="px-4 py-2 text-center text-green-700 transition border border-green-300 rounded-lg bg-green-50 hover:bg-green-100">
                    <i class="mr-2 pi pi-upload"></i>
                    {{ mealData.imageUrl ? 'Change Image' : 'Upload Image' }}
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleImageSelect"
                  >
                </label>
                <p v-if="mealData.existingImageName" class="mt-1 text-xs text-gray-500">
                  Current: {{ mealData.existingImageName }}
                </p>
              </div>
            </div>

            <!-- Special Notes -->
            <div>
              <label for="note" class="block mb-1 text-sm font-medium text-gray-700">Special Notes</label>
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
              <label class="block mb-1 text-sm font-medium text-gray-700">Meal Items</label>
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

              <ul v-if="mealData.items.length > 0" class="overflow-y-auto divide-y divide-gray-200 rounded-lg max-h-48 bg-gray-50">
                <li v-for="(item, index) in mealData.items" :key="index" class="flex items-center justify-between px-3 py-2">
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
              <p v-else class="py-2 text-sm italic text-center text-gray-500">
                No items added yet
              </p>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex flex-col justify-end mt-8 space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
          <button
            type="button"
            @click="goBack"
            class="px-6 py-2 text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="flex items-center justify-center px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
            :disabled="isSaving"
          >
            <span v-if="isSaving" class="w-4 h-4 mr-2 border-2 border-t-2 border-white rounded-full animate-spin"></span>
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
