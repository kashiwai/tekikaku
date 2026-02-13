import { createClient } from '@supabase/supabase-js'

const supabaseUrl = 'https://syhkijjmcjvebtsdrwjo.supabase.co'
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5aGtpamptY2p2ZWJ0c2Ryd2pvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA5MDY0NTcsImV4cCI6MjA4NjQ4MjQ1N30.uX4UoT4jyvOWDD4gTQt_-HinsGv_T0ADjFzXg_P4WzY'

export const supabase = createClient(supabaseUrl, supabaseAnonKey);

// Import the supabase client like this:
// For React:
// import { supabase } from "@/integrations/supabase/client";
// For React Native:
// import { supabase } from "@/src/integrations/supabase/client";
