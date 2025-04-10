import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import 'primeicons/primeicons.css';
import Parse from 'parse/dist/parse.min.js';

// Load environment variables
const PARSE_APPLICATION_ID = import.meta.env.VITE_PARSE_APPLICATION_ID;
const PARSE_HOST_URL = import.meta.env.VITE_PARSE_HOST_URL;
const PARSE_JAVASCRIPT_KEY = import.meta.env.VITE_PARSE_JAVASCRIPT_KEY;

// Check if environment variables are loaded
if (!PARSE_APPLICATION_ID || !PARSE_HOST_URL) {
  console.error("Parse environment variables are missing. Check your .env file.");
} else {
  try {
    // Parse.initialize doesn't return a value, so we can't check it with an if statement
    Parse.initialize(PARSE_APPLICATION_ID, PARSE_JAVASCRIPT_KEY);
    Parse.serverURL = PARSE_HOST_URL;
    console.log("Parse initialized successfully");
  } catch (error) {
    console.error("Failed to initialize Parse:", error);
  }
}

const app = createApp(App)

app.use(router)

app.mount('#app')