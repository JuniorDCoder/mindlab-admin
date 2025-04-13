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

// Define meal data structure
const mealData = ref({
  name: '',
  description: '',
  mealType: 'snack',
  image: null,
  schedule: null,
  note: '',
  items: [],
  ingredients: [],
  parseObject: null,
  imageUrl: null,
  existingImageName: null
});

// Options for meal types
const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'premium'];

// Ingredients management
const allIngredients = ref([]);
const loadingIngredients = ref(false);
const ingredientSearchQuery = ref('');
const filteredIngredients = ref([]);
const showIngredientDropdown = ref(false);

// Image handling
const imageFile = ref(null);
const imagePreview = ref(null);

// Fetch all ingredients from Parse
const fetchAllIngredients = async () => {
  loadingIngredients.value = true;
  try {
    const Ingredient = Parse.Object.extend('Ingredients');
    const query = new Parse.Query(Ingredient);
    query.limit(1000);
    const results = await query.find();

    allIngredients.value = results.map(ingredient => ({
      id: ingredient.id,
      name: ingredient.get('name') || 'Unnamed Ingredient',
      imageUrl: ingredient.get('image')?.url() || null,
      parseObject: ingredient
    }));

    filteredIngredients.value = [...allIngredients.value];
  } catch (error) {
    console.error('Error fetching ingredients:', error);
    errorMessage.value = 'Failed to load ingredients.';
  } finally {
    loadingIngredients.value = false;
  }
};

// Filter ingredients based on search
const filterIngredients = () => {
  if (!ingredientSearchQuery.value) {
    filteredIngredients.value = [...allIngredients.value];
    return;
  }

  const query = ingredientSearchQuery.value.toLowerCase();
  filteredIngredients.value = allIngredients.value.filter(ingredient =>
    ingredient.name.toLowerCase().includes(query)
  );
};

// Add ingredient to meal
const addIngredient = (ingredient) => {
  if (!mealData.value.ingredients.some(i => i.id === ingredient.id)) {
    mealData.value.ingredients.push(ingredient);
  }
  showIngredientDropdown.value = false;
  ingredientSearchQuery.value = '';
};

// Remove ingredient from meal
const removeIngredient = (index) => {
  mealData.value.ingredients.splice(index, 1);
};

// Fetch meal details from Parse
const fetchMealDetails = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const Meal = Parse.Object.extend('Meals');
    const query = new Parse.Query(Meal);

    // Include ingredients relation
    query.include('ingredients');

    const result = await query.get(mealId);

    if (!result) {
      throw new Error('Meal not found');
    }

    // Store original Parse object reference
    mealData.value.parseObject = result;

    // Populate form with existing data
    mealData.value.name = result.get('name') || '';
    mealData.value.description = result.get('description') || '';
    mealData.value.mealType = result.get('mealType') || 'regular';
    mealData.value.note = result.get('note') || '';
    mealData.value.items = result.get('items') || [];

    // Handle ingredients - using the relation
    const ingredientsRelation = result.relation('ingredients');
    const ingredientsQuery = ingredientsRelation.query();
    const ingredientsResults = await ingredientsQuery.find();

    // Safely map ingredients
    mealData.value.ingredients = Array.isArray(ingredientsResults) ?
      ingredientsResults.map(ingredient => ({
        id: ingredient.id,
        name: ingredient.get('name') || 'Unnamed Ingredient',
        imageUrl: ingredient.get('image')?.url() || null,
        parseObject: ingredient
      })) : [];

    // Handle date fields
    const scheduleDate = result.get('schedule');
    if (scheduleDate) {
      const formattedDate = new Date(scheduleDate).toISOString().split('T')[0];
      mealData.value.schedule = formattedDate;
    }

    // Store image info if exists
    const existingImage = result.get('image');
    if (existingImage) {
      mealData.value.imageUrl = existingImage.url();
      mealData.value.existingImageName = existingImage.name();
    }

  } catch (error) {
    console.error('Error fetching meal details:', error);
    errorMessage.value = 'Failed to load meal details. Please try again later.';
  } finally {
    isLoading.value = false;
  }
};

// Add a new item to the items array
const newItem = ref('');
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
    const mealObject = mealData.value.parseObject;

    // Update basic fields
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

    // Handle ingredients relation
    const ingredientsRelation = mealObject.relation('ingredients');

    // First remove all existing ingredients
    const existingQuery = ingredientsRelation.query();
    const existingIngredients = await existingQuery.find();
    existingIngredients.forEach(ingredient => {
      ingredientsRelation.remove(ingredient);
    });

    // Then add the new ingredients
    mealData.value.ingredients.forEach(ingredient => {
      const ingredientPointer = Parse.Object.extend('Ingredients').createWithoutData(ingredient.id);
      ingredientsRelation.add(ingredientPointer);
    });

    // Save the updated object
    await mealObject.save();

    successMessage.value = 'Meal updated successfully!';

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
  fetchAllIngredients();
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
          </div>
        </div>

        <!-- Ingredients Section -->
        <div class="p-6 mt-8 rounded-lg bg-gray-50">
          <h2 class="mb-4 text-lg font-semibold text-gray-800">Ingredients</h2>

          <!-- Selected Ingredients -->
          <div v-if="mealData.ingredients.length > 0" class="mb-6">
            <h3 class="mb-2 text-sm font-medium text-gray-700">Selected Ingredients</h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
              <div v-for="(ingredient, index) in mealData.ingredients" :key="ingredient.id"
                   class="flex items-center p-3 bg-white border rounded-lg shadow-sm">
                <div v-if="ingredient.imageUrl" class="flex-shrink-0 w-10 h-10 mr-3 overflow-hidden rounded-full">
                  <img :src="ingredient.imageUrl" :alt="ingredient.name" class="object-cover w-full h-full">
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ ingredient.name }}</p>
                </div>
                <button @click="removeIngredient(index)" class="ml-2 text-red-500 hover:text-red-700">
                  <i class="pi pi-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Add Ingredients -->
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-700">Add Ingredients</label>
            <div class="relative">
              <div class="flex items-center px-3 py-2 border border-gray-300 rounded-lg">
                <i class="mr-2 text-gray-400 pi pi-search"></i>
                <input
                  type="text"
                  v-model="ingredientSearchQuery"
                  @focus="showIngredientDropdown = true"
                  placeholder="Search ingredients to add"
                  class="flex-1 bg-transparent focus:outline-none"
                />
              </div>

              <!-- Ingredients Dropdown -->
              <div v-if="showIngredientDropdown" class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-lg max-h-64">
                <div v-if="loadingIngredients" class="flex items-center justify-center p-4">
                  <div class="w-5 h-5 border-t-2 border-b-2 border-green-600 rounded-full animate-spin"></div>
                  <span class="ml-2 text-sm text-gray-600">Loading ingredients...</span>
                </div>

                <div v-else-if="filteredIngredients.length === 0" class="p-3 text-sm text-gray-500">
                  No ingredients found matching your search
                </div>

                <div v-else>
                  <div v-for="ingredient in filteredIngredients" :key="ingredient.id"
                       @click="addIngredient(ingredient)"
                       class="flex items-center p-3 cursor-pointer hover:bg-gray-100">
                    <div v-if="ingredient.imageUrl" class="flex-shrink-0 w-8 h-8 mr-3 overflow-hidden rounded-full">
                      <img :src="ingredient.imageUrl" :alt="ingredient.name" class="object-cover w-full h-full">
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ ingredient.name }}</p>
                    </div>
                    <i class="ml-2 text-green-500 pi pi-plus"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Meal Items Section -->
        <div class="p-6 mt-6 rounded-lg bg-gray-50">
          <h2 class="mb-4 text-lg font-semibold text-gray-800">Meal Items</h2>

          <div class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Add Items</label>
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

            <ul v-if="mealData.items.length > 0" class="overflow-y-auto divide-y divide-gray-200 rounded-lg bg-gray-50 max-h-48">
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
