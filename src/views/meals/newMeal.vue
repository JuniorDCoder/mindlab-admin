<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Define meal data structure
const mealData = reactive({
  name: '',
  description: '',
  mealType: 'breakfast',
  items: [],
  ingredients: [], // Added ingredients array
  schedule: '',
  note: ''
});

// Available meal types
const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'premium'];

// Ingredients management
const allIngredients = ref([]);
const loadingIngredients = ref(false);
const ingredientSearchQuery = ref('');
const filteredIngredients = ref([]);
const showIngredientDropdown = ref(false);

// For dynamically adding meal items
const newItem = ref('');

// Handle image input
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
  if (!mealData.ingredients.some(i => i.id === ingredient.id)) {
    mealData.ingredients.push(ingredient);
  }
  showIngredientDropdown.value = false;
  ingredientSearchQuery.value = '';
};

// Remove ingredient from meal
const removeIngredient = (index) => {
  mealData.ingredients.splice(index, 1);
};

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

    // Save meal to Parse first to get an ID
    const savedMeal = await meal.save();

    // Handle ingredients relation if any ingredients were added
    if (mealData.ingredients.length > 0) {
      const ingredientsRelation = savedMeal.relation('ingredients');
      mealData.ingredients.forEach(ingredient => {
        const ingredientPointer = Parse.Object.extend('Ingredients').createWithoutData(ingredient.id);
        ingredientsRelation.add(ingredientPointer);
      });

      // Save the relation changes
      await savedMeal.save();
    }

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

// Load ingredients when component mounts
fetchAllIngredients();
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
            </div>
          </div>

          <!-- Ingredients Section -->
          <div class="p-6 mt-6 rounded-lg bg-gray-50">
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
