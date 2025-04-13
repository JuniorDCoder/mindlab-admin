<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">Ingredients</h1>
          <p class="text-green-600">Manage all available ingredients</p>
        </div>
        <router-link
          to="/ingredients/create"
          class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          <i class="mr-2 pi pi-plus"></i>
          Add New Ingredient
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-6 mb-6 bg-white shadow-md rounded-xl">
        <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
          <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
          <div>
            <h3 class="font-medium">Error Loading Ingredients</h3>
            <p>{{ error }}</p>
          </div>
        </div>
        <button
          @click="fetchIngredients"
          class="px-4 py-2 mt-4 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          Retry
        </button>
      </div>

      <!-- Ingredients Grid -->
      <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Ingredient Card -->
        <div
          v-for="ingredient in ingredients"
          :key="ingredient.id"
          class="overflow-hidden transition-all duration-300 bg-white shadow-md rounded-xl hover:shadow-lg hover:-translate-y-1"
        >
          <!-- Image -->
          <div class="relative h-48 overflow-hidden bg-gray-100">
            <img
              v-if="ingredient.imageUrl"
              :src="ingredient.imageUrl"
              :alt="ingredient.name"
              class="object-cover w-full h-full"
            >
            <div v-else class="flex items-center justify-center w-full h-full text-gray-400">
              <i class="text-5xl pi pi-image"></i>
            </div>
          </div>

          <!-- Content -->
          <div class="p-5">
            <h3 class="mb-2 text-xl font-semibold text-gray-800">{{ ingredient.name }}</h3>

            <!-- Meta -->
            <div class="flex items-center mb-4 text-sm text-gray-500">
              <i class="mr-2 pi pi-clock"></i>
              <span>Created: {{ formatDate(ingredient.createdAt) }}</span>
            </div>

            <!-- Actions -->
            <div class="flex space-x-2">
              <router-link
                :to="`/ingredients/${ingredient.id}`"
                class="flex-1 px-3 py-2 text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                <i class="pi pi-eye"></i> View
              </router-link>
              <router-link
                :to="`/ingredients/${ingredient.id}/edit`"
                class="flex-1 px-3 py-2 text-center text-white bg-yellow-600 rounded-lg hover:bg-yellow-700"
              >
                <i class="pi pi-pencil"></i> Edit
              </router-link>
              <button
                @click="confirmDelete(ingredient)"
                class="flex-1 px-3 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
              >
                <i class="pi pi-trash"></i> Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && !error && ingredients.length === 0" class="p-8 text-center bg-white rounded-xl">
        <div class="mx-auto mb-4 text-green-500 w-14 h-14">
          <i class="text-5xl pi pi-inbox"></i>
        </div>
        <h3 class="mb-2 text-xl font-medium text-gray-800">No Ingredients Found</h3>
        <p class="mb-4 text-gray-600">You haven't added any ingredients yet.</p>
        <router-link
          to="/ingredients/create"
          class="inline-flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          <i class="mr-2 pi pi-plus"></i>
          Add Your First Ingredient
        </router-link>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md p-6 mx-4 bg-white rounded-lg shadow-xl">
          <div class="mb-4 text-xl font-semibold text-gray-800">Confirm Deletion</div>
          <p class="mb-6 text-gray-600">Are you sure you want to delete "{{ ingredientToDelete?.name }}"? This action cannot be undone.</p>
          <div class="flex justify-end space-x-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              @click="deleteIngredient"
              :disabled="deleting"
              class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              <span v-if="deleting" class="inline-block w-4 h-4 mr-2 border-2 border-white rounded-full border-t-transparent animate-spin"></span>
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Parse from 'parse/dist/parse.min.js';

const ingredients = ref([]);
const loading = ref(true);
const error = ref(null);
const showDeleteModal = ref(false);
const ingredientToDelete = ref(null);
const deleting = ref(false);

// Format date for display
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Fetch all ingredients
const fetchIngredients = async () => {
  loading.value = true;
  error.value = null;
  try {
    const Ingredient = Parse.Object.extend('Ingredients');
    const query = new Parse.Query(Ingredient);
    query.descending('createdAt');
    const results = await query.find();

    ingredients.value = results.map(ingredient => ({
      id: ingredient.id,
      name: ingredient.get('name'),
      imageUrl: ingredient.get('image')?.url(),
      createdAt: ingredient.createdAt,
      updatedAt: ingredient.updatedAt,
      parseObject: ingredient
    }));
  } catch (err) {
    console.error('Error fetching ingredients:', err);
    error.value = 'Failed to load ingredients. Please try again.';
  } finally {
    loading.value = false;
  }
};

// Confirm delete action
const confirmDelete = (ingredient) => {
  ingredientToDelete.value = ingredient;
  showDeleteModal.value = true;
};

// Delete ingredient
const deleteIngredient = async () => {
  deleting.value = true;
  try {
    await ingredientToDelete.value.parseObject.destroy();
    ingredients.value = ingredients.value.filter(i => i.id !== ingredientToDelete.value.id);
    showDeleteModal.value = false;
  } catch (err) {
    console.error('Error deleting ingredient:', err);
    error.value = 'Failed to delete ingredient. Please try again.';
  } finally {
    deleting.value = false;
  }
};

// Load data on component mount
onMounted(() => {
  fetchIngredients();
});
</script>

<style scoped>
/* Animation for cards */
.ingredient-enter-active,
.ingredient-leave-active {
  transition: all 0.3s ease;
}
.ingredient-enter-from,
.ingredient-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
