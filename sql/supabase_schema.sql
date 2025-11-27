-- Supabase Schema para CineVision (v2 com Auth + Admin)
-- Executar no Supabase Dashboard → SQL
-- Atualizado em 26/11/2025

-- Perfis vinculados ao Supabase Auth
CREATE TABLE IF NOT EXISTS public.profiles (
  id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  display_name text,
  email text UNIQUE,
  role text NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'user')),
  created_at timestamptz DEFAULT now(),
  last_access_at timestamptz,
  disabled boolean NOT NULL DEFAULT false
);

-- Índices para profiles
CREATE UNIQUE INDEX IF NOT EXISTS profiles_email_key ON public.profiles(email);
CREATE INDEX IF NOT EXISTS profiles_role_idx ON public.profiles(role);

ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- Políticas RLS para profiles
-- Usuários podem ver seu próprio perfil
CREATE POLICY "Users can view own profile" ON public.profiles
FOR SELECT USING (id = auth.uid());

-- Usuários podem inserir seu próprio perfil
CREATE POLICY "Users can insert own profile" ON public.profiles
FOR INSERT WITH CHECK (id = auth.uid());

-- Usuários podem atualizar seu próprio perfil
CREATE POLICY "Users can update own profile" ON public.profiles
FOR UPDATE USING (id = auth.uid())
WITH CHECK (id = auth.uid());

-- Trigger para criar perfil automaticamente quando usuário é criado no Auth
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.profiles (id, display_name, email, role, created_at, disabled)
  VALUES (
    NEW.id, 
    COALESCE(NEW.raw_user_meta_data->>'display_name', ''), 
    NEW.email,
    CASE 
      WHEN NEW.email = 'williamzenf5@gmail.com' THEN 'admin'
      ELSE 'user'
    END,
    NOW(),
    false
  )
  ON CONFLICT (id) DO UPDATE SET
    email = EXCLUDED.email,
    display_name = CASE 
      WHEN public.profiles.display_name IS NULL OR public.profiles.display_name = '' 
      THEN EXCLUDED.display_name 
      ELSE public.profiles.display_name 
    END;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Criar trigger na tabela auth.users
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- Admins podem ver todos os perfis
CREATE POLICY "Admins can view all profiles" ON public.profiles
FOR SELECT USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role = 'admin')
);

-- Admins podem atualizar todos os perfis
CREATE POLICY "Admins can update all profiles" ON public.profiles
FOR UPDATE USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role = 'admin')
)
WITH CHECK (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role = 'admin')
);

-- Admins podem deletar perfis
CREATE POLICY "Admins can delete all profiles" ON public.profiles
FOR DELETE USING (
    EXISTS (SELECT 1 FROM public.profiles WHERE id = auth.uid() AND role = 'admin')
);

-- Configurações do usuário (uuid)
CREATE TABLE IF NOT EXISTS public.user_settings_v2 (
  user_id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  subtitle_lang varchar(10) DEFAULT 'pob',
  quality_pref varchar(20) DEFAULT '1080p',
  rd_enabled boolean DEFAULT false,
  rd_token text,
  rd_username varchar(100)
);

ALTER TABLE public.user_settings_v2 ENABLE ROW LEVEL SECURITY;
CREATE POLICY user_settings_v2_select ON public.user_settings_v2 FOR SELECT USING (user_id = auth.uid());
CREATE POLICY user_settings_v2_insert ON public.user_settings_v2 FOR INSERT WITH CHECK (user_id = auth.uid());
CREATE POLICY user_settings_v2_update ON public.user_settings_v2 FOR UPDATE USING (user_id = auth.uid()) WITH CHECK (user_id = auth.uid());
CREATE POLICY user_settings_v2_delete ON public.user_settings_v2 FOR DELETE USING (user_id = auth.uid());

-- Favoritos (uuid)
CREATE TABLE IF NOT EXISTS public.favorites_v2 (
  id bigserial PRIMARY KEY,
  user_id uuid REFERENCES auth.users(id) ON DELETE CASCADE,
  imdb_id varchar(20) NOT NULL,
  type varchar(10) NOT NULL CHECK (type IN ('movie','series')),
  title varchar(255) NOT NULL,
  poster varchar(500),
  year varchar(10),
  added_at timestamptz DEFAULT now(),
  UNIQUE(user_id, imdb_id)
);

ALTER TABLE public.favorites_v2 ENABLE ROW LEVEL SECURITY;
CREATE POLICY favorites_v2_select ON public.favorites_v2 FOR SELECT USING (user_id = auth.uid());
CREATE POLICY favorites_v2_insert ON public.favorites_v2 FOR INSERT WITH CHECK (user_id = auth.uid());
CREATE POLICY favorites_v2_update ON public.favorites_v2 FOR UPDATE USING (user_id = auth.uid()) WITH CHECK (user_id = auth.uid());
CREATE POLICY favorites_v2_delete ON public.favorites_v2 FOR DELETE USING (user_id = auth.uid());

-- Índices
CREATE INDEX IF NOT EXISTS idx_favorites_v2_user_id ON public.favorites_v2(user_id);
CREATE INDEX IF NOT EXISTS idx_favorites_v2_imdb_id ON public.favorites_v2(imdb_id);

-- Tabela de progresso de visualização
CREATE TABLE IF NOT EXISTS public.watch_progress (
  id bigserial PRIMARY KEY,
  user_id uuid REFERENCES auth.users(id) ON DELETE CASCADE,
  imdb_id varchar(20) NOT NULL,
  type varchar(10) NOT NULL CHECK (type IN ('movie','series')),
  title varchar(255) NOT NULL,
  poster varchar(500),
  year varchar(10),
  season int NOT NULL DEFAULT 0,  -- 0 para filmes
  episode int NOT NULL DEFAULT 0, -- 0 para filmes
  current_time_sec float NOT NULL DEFAULT 0,
  duration_sec float NOT NULL DEFAULT 0,
  percent_watched int NOT NULL DEFAULT 0,
  completed boolean NOT NULL DEFAULT false,
  -- Stream info for resuming with the same source
  stream_index int DEFAULT 0,
  stream_infohash varchar(100),
  stream_url text,
  stream_title text,
  last_watched_at timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now(),
  -- Constraint única para upsert funcionar
  UNIQUE (user_id, imdb_id, season, episode)
);

ALTER TABLE public.watch_progress ENABLE ROW LEVEL SECURITY;

-- Políticas RLS para watch_progress
CREATE POLICY watch_progress_select ON public.watch_progress 
  FOR SELECT USING (user_id = auth.uid());
CREATE POLICY watch_progress_insert ON public.watch_progress 
  FOR INSERT WITH CHECK (user_id = auth.uid());
CREATE POLICY watch_progress_update ON public.watch_progress 
  FOR UPDATE USING (user_id = auth.uid()) WITH CHECK (user_id = auth.uid());
CREATE POLICY watch_progress_delete ON public.watch_progress 
  FOR DELETE USING (user_id = auth.uid());

-- Índices para watch_progress
CREATE INDEX IF NOT EXISTS idx_watch_progress_user_id ON public.watch_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_watch_progress_imdb_id ON public.watch_progress(imdb_id);
CREATE INDEX IF NOT EXISTS idx_watch_progress_last_watched ON public.watch_progress(last_watched_at DESC);
