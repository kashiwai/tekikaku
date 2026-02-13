-- Create contact inquiries table
CREATE TABLE IF NOT EXISTS public.contact_inquiries_2026_02_12_14_20 (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  organization TEXT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL
);

-- Create RLS policies
ALTER TABLE public.contact_inquiries_2026_02_12_14_20 ENABLE ROW LEVEL SECURITY;

-- Policy for inserting new inquiries (anyone can submit)
CREATE POLICY "Anyone can insert contact inquiries" ON public.contact_inquiries_2026_02_12_14_20
  FOR INSERT WITH CHECK (true);

-- Policy for viewing inquiries (only authenticated users)
CREATE POLICY "Authenticated users can view inquiries" ON public.contact_inquiries_2026_02_12_14_20
  FOR SELECT USING (auth.role() = 'authenticated');

-- Create updated_at trigger
CREATE OR REPLACE FUNCTION public.handle_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = TIMEZONE('utc'::text, NOW());
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER handle_updated_at_contact_inquiries_2026_02_12_14_20
  BEFORE UPDATE ON public.contact_inquiries_2026_02_12_14_20
  FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();