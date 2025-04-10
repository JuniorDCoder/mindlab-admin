import { createRouter, createWebHistory } from 'vue-router'
import communityView from '@/views/communityView.vue';
import HealthView from '@/components/Health.vue';
import mealView from '@/views/mealView.vue';
import subscribeView from '@/views/subscribeView.vue';
import homeView from '@/views/HomeView.vue';
import signIn from '@/components/signIn.vue';
import mealPlanView from '@/views/mealPlanView.vue';
import EditMeal from '@/components/editMeal.vue';
import newMeal from '@/components/newMeal.vue';
import Parse from 'parse/dist/parse.min.js';
import ProfileView from '@/views/profileView.vue';
import HealthRecord from '@/views/healthRecord.vue';
import Ingredients from '@/views/ingredients.vue';
import meals from '@/views/meals.vue';
import notes from '@/views/notes.vue';
import profiles from '@/views/profiles.vue';
import menuItem from '@/views/menuItem.vue';
import MealPlan from '@/views/mealPlan.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/dashboard',
      name: 'dashboard',
      component: HealthView,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/newmeal',
      name: 'newmeal',
      component: newMeal,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/meal-plan/:id',
      name: 'mealplan',
     component: mealPlanView,
     meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/meal/:id',
      name: 'mealView',
     component: mealView,
     meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/edit-meal/:id',
      name: 'editmeal',
     component: EditMeal,
     meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/signin',
      name: 'signin',
      component: signIn,
      meta: { showSidebar: false, requiresAuth: true }
    },
    {
      path: '/',
      name: 'home',
      component: homeView,
      meta: { showSidebar: false, requiresGuest: true }
    },
    {
      path: '/meal-plan',
      name: 'MealPlans',
      component: MealPlan,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/subscriptions',
      name: 'subscriptions',
      component: subscribeView,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/community',
      name: 'community',
      component: communityView,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/health-record',
      name: 'health-record',
      component: HealthRecord,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/health-record',
      name: 'health-record',
      component: HealthRecord,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/ingredients',
      name: 'ingredients',
      component: Ingredients,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/meals',
      name: 'meals',
      component: meals,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/notes',
      name: 'notes',
      component: notes,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/profiles',
      name: 'profiles',
      component: profiles,
      meta: { showSidebar: true, requiresAuth: true }
    },
    {
      path: '/menu-item',
      name: 'menu-item',
      component: menuItem,
      meta: { showSidebar: true, requiresAuth: true }
    },
  ],
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  try {
    // Check for stored session token
    const sessionToken = localStorage.getItem('sessionToken');

    // Try to restore session if token exists
    if (sessionToken) {
      try {
        Parse.User.become(sessionToken).catch(() => {
          // Token expired or invalid, remove it
          localStorage.removeItem('sessionToken');
        });
      } catch (error) {
        console.log('Session restoration failed:', error);
        localStorage.removeItem('sessionToken');
      }
    }

    // Get current user state
    const currentUser = Parse.User.current();
    const isAuthenticated = currentUser !== null;

    // If route requires auth and user isn't authenticated
    if (to.meta.requiresAuth && !isAuthenticated) {
      next('/');
      return;
    }

    // If route requires guest and user is authenticated (like the login page)
    if (to.meta.requiresGuest && isAuthenticated) {
      next('/dashboard');
      return;
    }

    // Otherwise proceed as normal
    next();
  } catch (error) {
    console.error('Navigation guard error:', error);
    next('/');
  }
})


export default router;
