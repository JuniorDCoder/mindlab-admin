<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">Edit Ingredient</h1>
          <p class="text-green-600">Update ingredient details</p>
        </div>
        <button
          @click="cancelEdit"
          class="flex items-center px-4 py-2 text-green-800 hover:text-green-600"
        >
          <i class="mr-2 pi pi-arrow-left"></i> Back
        </button>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="p-6 mb-6 bg-white shadow-md rounded-xl">
        <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
          <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
          <div>
            <h3 class="font-medium">Error</h3>
            <p>{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Edit form -->
      <div v-else class="overflow-hidden bg-white shadow-md rounded-xl">
        <form @submit.prevent="updateIngredient" class="p-6">
          <!-- Success message -->
          <div v-if="success" class="p-4 mb-6 text-green-700 bg-green-100 rounded-lg">
            <div class="flex items-center">
              <i class="mr-3 text-2xl pi pi-check-circle"></i>
              <div>
                <h3 class="font-medium">Success!</h3>
                <p>Ingredient updated successfully</p>
              </div>
            </div>
          </div>

          <!-- Form fields -->
          <div class="grid gap-6 md:grid-cols-2">
            <!-- Left column -->
            <div>
              <!-- Name -->
              <div class="mb-6">
                <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                  Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="ingredient.name"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                >
              </div>

              <!-- Description -->
              <div class="mb-6">
                <label for="description" class="block mb-2 text-sm font-medium text-gray-700">
                  Description
                </label>
                <textarea
                  id="description"
                  v-model="ingredient.description"
                  rows="4"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                ></textarea>
              </div>
            </div>

            <!-- Right column -->
            <div>
              <!-- Image upload -->
              <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                  Image
                </label>
                <div class="flex flex-col items-center">
                  <!-- Image preview -->
                  <div class="w-full mb-4 overflow-hidden bg-gray-100 rounded-lg aspect-square">
                    <img
                      v-if="imagePreview || ingredient.imageUrl"
                      :src="imagePreview || ingredient.imageUrl"
                      alt="Ingredient preview"
                      class="object-cover w-full h-full"
                    >
                    <div v-else class="flex items-center justify-center w-full h-full text-gray-400">
                      <i class="text-5xl pi pi-image"></i>
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
            </div>
          </div>

          <!-- Form actions -->
          <div class="flex justify-end mt-6 space-x-3">
            <button
              type="button"
              @click="cancelEdit"
              class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
            >
              <span v-if="saving" class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></span>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const ingredientId = route.params.id;

const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const success = ref(false);
const imageFile = ref(null);
const imagePreview = ref(null);

const ingredient = ref({
  id: '',
  name: '',
  description: '',
  imageUrl: '',
  parseObject: null
});

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

// Fetch ingredient details
const fetchIngredient = async () => {
  loading.value = true;
  error.value = null;
  try {
    const Ingredient = Parse.Object.extend('Ingredients');
    const query = new Parse.Query(Ingredient);
    const result = await query.get(ingredientId);

    ingredient.value = {
      id: result.id,
      name: result.get('name'),
      description: result.get('description') || '',
      imageUrl: result.get('image')?.url() || '',
      parseObject: result
    };
  } catch (err) {
    console.error('Error fetching ingredient:', err);
    error.value = 'Failed to load ingredient. It may have been deleted.';
  } finally {
    loading.value = false;
  }
};

// Update ingredient
const updateIngredient = async () => {
  saving.value = true;
  error.value = null;
  success.value = false;

  try {
    const ingredientObj = ingredient.value.parseObject;

    // Update fields
    ingredientObj.set('name', ingredient.value.name);
    ingredientObj.set('description', ingredient.value.description);

    // Handle image upload if a new image was selected
    if (imageFile.value) {
      const parseFile = new Parse.File(imageFile.value.name, imageFile.value);
      await parseFile.save();
      ingredientObj.set('image', parseFile);
    }

    // Save the updated object
    await ingredientObj.save();

    success.value = true;
    setTimeout(() => {
      router.push(`/ingredients/${ingredientId}`);
    }, 1500);
  } catch (err) {
    console.error('Error updating ingredient:', err);
    error.value = 'Failed to update ingredient. Please try again.';
  } finally {
    saving.value = false;
  }
};

// Cancel edit
const cancelEdit = () => {
  router.push(`/ingredients/${ingredientId}`);
};

// Load data on component mount
onMounted(() => {
  fetchIngredient();
});
</script>
