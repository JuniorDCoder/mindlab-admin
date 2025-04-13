<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-green-800">Add New Ingredient</h1>
          <p class="text-green-600">Create a new ingredient for your recipes</p>
        </div>
        <button
          @click="cancelCreate"
          class="flex items-center px-4 py-2 text-green-800 hover:text-green-600"
        >
          <i class="mr-2 pi pi-arrow-left"></i> Back
        </button>
      </div>

      <!-- Form -->
      <div class="overflow-hidden bg-white shadow-md rounded-xl">
        <form @submit.prevent="createIngredient" class="p-6">
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
                <h3 class="font-medium">Success!</h3>
                <p>Ingredient created successfully. Redirecting...</p>
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
                  placeholder="Enter ingredient name"
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
                  placeholder="Enter ingredient description (optional)"
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
                      v-if="imagePreview"
                      :src="imagePreview"
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
              @click="cancelCreate"
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
              {{ saving ? 'Creating...' : 'Create Ingredient' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();

const ingredient = ref({
  name: '',
  description: '',
});

const imageFile = ref(null);
const imagePreview = ref(null);
const saving = ref(false);
const error = ref(null);
const success = ref(false);

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

// Create new ingredient
const createIngredient = async () => {
  saving.value = true;
  error.value = null;
  success.value = false;

  try {
    // Validate required fields
    if (!ingredient.value.name.trim()) {
      throw new Error('Ingredient name is required');
    }

    // Create new Parse object
    const Ingredient = Parse.Object.extend('Ingredients');
    const newIngredient = new Ingredient();

    // Set basic fields
    newIngredient.set('name', ingredient.value.name);
    newIngredient.set('description', ingredient.value.description || '');

    // Handle image upload if selected
    if (imageFile.value) {
      const parseFile = new Parse.File(imageFile.value.name, imageFile.value);
      await parseFile.save();
      newIngredient.set('image', parseFile);
    }

    // Set ACL (Access Control List)
    const acl = new Parse.ACL();
    acl.setPublicReadAccess(true);
    acl.setPublicWriteAccess(true);
    newIngredient.setACL(acl);

    // Save to database
    const savedIngredient = await newIngredient.save();

    // Show success and redirect
    success.value = true;
    setTimeout(() => {
      router.push(`/ingredients/${savedIngredient.id}`);
    }, 1500);

  } catch (err) {
    console.error('Error creating ingredient:', err);
    error.value = err.message || 'Failed to create ingredient. Please try again.';
  } finally {
    saving.value = false;
  }
};

// Cancel creation
const cancelCreate = () => {
  router.push('/ingredients');
};
</script>

<style scoped>
/* Smooth transitions for form elements */
input, textarea, select {
  transition: all 0.3s ease;
}

/* Focus styles */
input:focus, textarea:focus, select:focus {
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  outline: none;
}

/* Image preview animation */
img {
  transition: transform 0.3s ease;
}

img:hover {
  transform: scale(1.02);
}
</style>
